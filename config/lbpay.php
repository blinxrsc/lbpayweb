<?php

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
];
