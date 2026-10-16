<?php

$heartbeat = max(30, (int) env('LBPAY_HEARTBEAT_SECONDS', 60));

return [
    /*
    | Maximum pulses the ESP32 will accept in ONE REMOTE_START.
    |
    | Single source of truth: pushed to every device in the CONFIG message
    | ("max_pulses", firmware v1.5.5+) and enforced here BEFORE a customer
    | is charged. Firmware v1.5.4 and older have a fixed limit of 100 and
    | cannot be changed remotely — set this to 100 until every device is
    | on v1.5.5, otherwise prices above 100 pulses will be refused by them.
    |
    | Example: pulse_price RM0.01 (RM1 = 100 pulses) and a RM10 max cycle
    | price needs at least 1000. Firmware hard ceiling is 2000.
    */
    'max_pulses_per_start' => (int) env('LBPAY_MAX_PULSES_PER_START', 1000),

    /*
    | Presence (F5/F6/F8).
    |
    | heartbeat_seconds  How often devices send a heartbeat. Firmware v1.5.6+
    |                    takes it from CONFIG ("heartbeat_s"); older firmware
    |                    is fixed at 60 s. Keep 60 until the fleet is on v1.5.6.
    | presence_ttl       Redis "online" key lifetime. A device that misses
    |                    ~2.5 heartbeats stops accepting payments.
    | offline_after      Watchdog: no heartbeat for this long -> marked offline
    |                    + Telegram alert (once per transition).
    */
    'heartbeat_seconds'       => $heartbeat,
    'presence_ttl_seconds'    => (int) env('LBPAY_PRESENCE_TTL', (int) ceil($heartbeat * 2.5)),
    'offline_after_seconds'   => (int) env('LBPAY_OFFLINE_AFTER', $heartbeat * 3),

    /*
    | MQTT bridge (F8).
    |
    | share_group  When set, telemetry/status are subscribed as EMQX shared
    |              subscriptions ($share/<group>/...), so you can run 2-3
    |              bridge containers and EMQX load-balances between them.
    |              Leave empty for a single bridge.
    | debug        Log every incoming message (very noisy — 11M+ lines/day
    |              at 8,000 devices). Off in production.
    */
    'mqtt_share_group' => env('MQTT_SHARE_GROUP') ?: null,
    'mqtt_bridge_debug' => (bool) env('MQTT_BRIDGE_DEBUG', false),

    /*
    | Paid-start reconciliation (F6).
    |
    | A paid start that got NO "received" ack from the device after
    | retry_after seconds is re-sent once with the SAME op_id (the device
    | ignores it if it already ran it). Anything still not "completed"
    | after review_after seconds is set to status "review" and reported.
    */
    'start_retry_after_seconds'  => (int) env('LBPAY_START_RETRY_AFTER', 90),
    'start_review_after_seconds' => (int) env('LBPAY_START_REVIEW_AFTER', 600),
];
