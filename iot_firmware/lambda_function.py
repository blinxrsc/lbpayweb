import json
import os
import boto3

IOT_DATA_ENDPOINT = os.environ["IOT_DATA_ENDPOINT"]  # e.g. https://xxxxx-ats.iot.ap-southeast-1.amazonaws.com
TOPIC_PREFIX = os.environ.get("TOPIC_PREFIX", "esp32")

iot = boto3.client("iot-data", endpoint_url=IOT_DATA_ENDPOINT)

def _resp(code, body):
    return {
        "statusCode": code,
        "headers": {
            "content-type": "application/json",
            "access-control-allow-origin": "*",
            "access-control-allow-methods": "POST,OPTIONS",
            "access-control-allow-headers": "content-type,authorization"
        },
        "body": json.dumps(body)
    }

def lambda_handler(event, context):
    # API Gateway HTTP API: body is string JSON
    try:
        body = event.get("body") or "{}"
        if isinstance(body, str):
            body = json.loads(body)

        thing = body["thingName"]          # e.g. esp32-001
        command = body["command"]          # "ON" / "OFF" or {"led":1}
        pin = int(body.get("pin", 2))      # optional, default GPIO2

        topic = f"{TOPIC_PREFIX}/{thing}/cmd"

        payload = {
            "command": command,
            "pin": pin
        }

        # Publish MQTT message via IoT Data Plane
        iot.publish(
            topic=topic,
            qos=1,
            payload=json.dumps(payload).encode("utf-8")
        )

        return _resp(200, {"ok": True, "publishedTo": topic, "payload": payload})

    except KeyError as e:
        return _resp(400, {"ok": False, "error": f"Missing field: {str(e)}"})
    except Exception as e:
        return _resp(500, {"ok": False, "error": str(e)})
