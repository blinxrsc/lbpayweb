#!/usr/bin/env bash
set -euo pipefail

# -------- EDIT THESE --------
THING_NAME="${1:-esp32-001}"   # pass as first argument or default
REGION="${AWS_REGION:-ap-southeast-1}"  # change if needed
POLICY_NAME="${THING_NAME}-policy"
OUT_DIR="./${THING_NAME}"
# ---------------------------

mkdir -p "${OUT_DIR}"

ACCOUNT_ID="$(aws sts get-caller-identity --query Account --output text --region "${REGION}")"

echo "==> Creating Thing: ${THING_NAME}"
aws iot create-thing \
  --thing-name "${THING_NAME}" \
  --region "${REGION}" >/dev/null || true

echo "==> Creating keys + certificate (ACTIVE)"
CERT_ARN="$(aws iot create-keys-and-certificate \
  --set-as-active \
  --certificate-pem-outfile "${OUT_DIR}/device.crt.pem" \
  --public-key-outfile "${OUT_DIR}/public.key.pem" \
  --private-key-outfile "${OUT_DIR}/private.key.pem" \
  --query certificateArn --output text \
  --region "${REGION}")"

echo "CERT_ARN=${CERT_ARN}" | tee "${OUT_DIR}/cert_arn.txt"

echo "==> Writing IoT policy JSON"
cat > "${OUT_DIR}/policy.json" <<EOF
{
  "Version": "2012-10-17",
  "Statement": [
    {
      "Effect": "Allow",
      "Action": "iot:Connect",
      "Resource": "arn:aws:iot:${REGION}:${ACCOUNT_ID}:client/\${iot:Connection.Thing.ThingName}",
      "Condition": {
        "Bool": { "iot:Connection.Thing.IsAttached": ["true"] }
      }
    },
    {
      "Effect": "Allow",
      "Action": ["iot:Subscribe"],
      "Resource": "arn:aws:iot:${REGION}:${ACCOUNT_ID}:topicfilter/esp32/\${iot:Connection.Thing.ThingName}/*"
    },
    {
      "Effect": "Allow",
      "Action": ["iot:Receive"],
      "Resource": "arn:aws:iot:${REGION}:${ACCOUNT_ID}:topic/esp32/\${iot:Connection.Thing.ThingName}/*"
    },
    {
      "Effect": "Allow",
      "Action": ["iot:Publish"],
      "Resource": "arn:aws:iot:${REGION}:${ACCOUNT_ID}:topic/esp32/\${iot:Connection.Thing.ThingName}/*"
    }
  ]
}
EOF

# Thing policy variables + connect clientId rules:
# Using ${iot:Connection.Thing.ThingName} ties permissions to the connecting Thing name. [1](https://docs.aws.amazon.com/iot/latest/developerguide/thing-policy-variables.html)

echo "==> Creating IoT policy: ${POLICY_NAME}"
aws iot create-policy \
  --policy-name "${POLICY_NAME}" \
  --policy-document "file://${OUT_DIR}/policy.json" \
  --region "${REGION}" >/dev/null || true

echo "==> Attaching policy to certificate"
aws iot attach-policy \
  --policy-name "${POLICY_NAME}" \
  --target "${CERT_ARN}" \
  --region "${REGION}" >/dev/null
# attach-policy attaches an IoT policy to the principal (certificate). [5](https://docs.aws.amazon.com/cli/latest/reference/iot/attach-policy.html)

echo "==> Attaching certificate to Thing (principal->thing)"
aws iot attach-thing-principal \
  --thing-name "${THING_NAME}" \
  --principal "${CERT_ARN}" \
  --region "${REGION}" >/dev/null
# attach-thing-principal attaches the cert principal to the Thing. [6](https://docs.aws.amazon.com/cli/latest/reference/iot/attach-thing-principal.html)

echo "==> Fetching IoT data endpoint (ATS) (recommended)"
IOT_ENDPOINT="$(aws iot describe-endpoint \
  --endpoint-type iot:Data-ATS \
  --query endpointAddress --output text \
  --region "${REGION}")"
echo "${IOT_ENDPOINT}" | tee "${OUT_DIR}/iot_endpoint.txt"
# describe-endpoint with iot:Data-ATS returns ATS endpoint and is recommended. [7](https://docs.aws.amazon.com/cli/latest/reference/iot/describe-endpoint.html)

echo "==> Downloading AmazonRootCA1.pem"
curl -fsSL "https://www.amazontrust.com/repository/AmazonRootCA1.pem" -o "${OUT_DIR}/AmazonRootCA1.pem"

echo "✅ Done!"
echo "Files in: ${OUT_DIR}"
echo "Endpoint: ${IOT_ENDPOINT}"