# LB Paylinker: System Reference & Scale Review

Snapshot reviewed: **2026-10-14** (`lbpayweb20261014` backend + `lbpayadminapp20261014` Flutter admin app).
Target rollout: **8,000 ESP32 controllers** across laundry machines and vending machines.
Purpose: shared context for future questions. Every answer should build on this file, step by step.

**Fix status:** F1–F4 are fixed by patch `lbpay-payment-fixes-F1-F4.patch` (2026-10-14; see project doc `LBPaylinker_Deploy_F1-F4.md`). Deployment to production has not been confirmed yet. F14 (pulse limit) is fixed by firmware v1.5.5 + patch `lbpay-pulse-limit-incremental.patch` (2026-10-16; see `LBPaylinker_Fix_Pulse_Limit.md`). F5–F12 are open.

---

## 1. Components (what runs where)

| Layer | Implementation | Notes |
|---|---|---|
| Web/API | Laravel 12 (PHP 8.3, Apache in `php:8.3-apache`), Livewire 4 + Volt, Breeze auth | Container `lbpayweb_app`, port 80 exposed internally only |
| Reverse proxy / TLS | Nginx Proxy Manager (`jc21/nginx-proxy-manager`) | Ports 80/443/81 (81 = NPM admin UI). `trustProxies('*')` |
| Queue | Laravel Horizon on Redis | Container `lbpayweb_worker` (`php artisan horizon`) |
| MQTT ingest | `php artisan app:mqtt-bridge` (php-mqtt/laravel-client) | Container `lbpayweb_mqtt_bridge`; subscribes `machines/#` |
| Scheduler | `php artisan schedule:work` | Runs `app:machine-watchdog` every minute |
| Cache/state | Redis (alpine, 1 GB limit, port 6379 **published to host**) | Heartbeats, status, start-acks |
| Database | MySQL on **AWS RDS** (ap-southeast-5 / Malaysia) | phpMyAdmin container also deployed |
| Broker | **EMQX** (private IP `172.31.38.200` from backend; devices use a public IP) | MQTT 3.1, port 1883, username/password auth, **no TLS** |
| Payments | **Fiuu** (MOLPay), MD5 vcode/skey signatures | Device payments + e-wallet top-ups |
| Alerts | Telegram bot (watchdog + admin remote-start) | |
| Firmware | Arduino ESP32: latest **v1.5.5** (2026-10-16, from user's v1.5.4); v1.5.0 was in the 2026-10-14 snapshot | PubSubClient, ArduinoJson, BLE provisioning (+ Wi-Fi scan since v1.5.4), HTTP OTA, Preferences. v1.5.3+: pulse output **GPIO4** (`LM_PULSE`, from the stock EQLink firmware), coin input GPIO14. v1.5.2+: non-blocking MQTT reconnect, 15 s WDT |
| Admin mobile app | Flutter `lb_admin_app` (dio, flutter_secure_storage, mobile_scanner, flutter_blue_plus, provider) | Talks to `https://lbpaylinker.com/api` with Sanctum tokens |

Domain: `lbpaylinker.com`. All containers bind-mount the project directory (`.:/var/www/html/lbpayweb`).

---

## 2. Domain model (key tables)

- `devices`: one row per ESP32 (`serial_number` unique, e.g. `NYJ312007A100216290`). Holds pricing (`washer_cold/warm/hot_price`, `dryer_low/med/hi_price`), `pulse_price`, `pulse_width`, `pulse_delay`, `coin_signal_width`, `coin_signal_sensitivity`, `pulse_pull_up`, `coin_signal_idle_high`, `pulse_active_low`, `max_vend_price`, `pulse_add_min`, `ota_status`, `ota_error`, `last_payload`, `status`, `supplier_id`.
- `device_has_outlet` (model `DeviceOutlet`): installs a device at an outlet: `outlet_id`, `machine_num`, `machine_name`, `machine_type` (Washer/Dryer), `device_serial_number`, `status`, `availability`, `current_coins`, `lifetime_coins`, `last_seen_at`, `last_reboot_at`.
- `outlets`, `brands`, `managers`, `suppliers`, `type_outlets`, `type_statuses`, `outlet_user` (outlet scoping for staff users).
- `customers` (separate `customer` auth guard), `ewallet_accounts` (`credit_balance`, `bonus_balance`), `ewallet_transactions`, `topup_packages`, `payment_gateway_transactions` (top-ups), `device_transactions` (machine payments: `order_id` unique, `provider` fiuu/ewallet, `status` initiated→paid→activated/failed, `meta.mode`, `meta.op_id`), `transaction_refunds`.
- Ops/audit: `remote_start_logs` (staff-triggered starts only), `device_audit_logs`, `device_movement_logs`, `firmware`, `health_statuses`, `activity_log` (spatie), `mail_servers`, `mail_server_logs`, `settings`, `merchant_configs`, `terms_of_services`.
- Auth/roles: `users` + spatie/permission (roles, permissions like `devices.manage`, `devices_outlet.manage/edit/delete`, `ewallet.manage`, `transactions.refund`, `reports.device-revenue`, `setting.backup`), Sanctum `personal_access_tokens` for the Flutter app.

---

## 3. MQTT contract

Topics (per device serial `{sn}`):

| Topic | Direction | Payloads |
|---|---|---|
| `machines/{sn}/telemetry` | device → server | Heartbeat every 60 s `{"status":"online","signal":-61,"coins":0}`; one message per physical coin `{"status":"online","coins":1}`; **LWT** `{"status":"offline"}` (QoS 1, **retained**) |
| `machines/{sn}/status` | device → server | `{"event":"start_ack","op_id","status":"received|completed|busy|rejected|duplicate_ignored|failed","error"}`; `{"event":"config_ack","status":"applied"}`; OTA `{"ota_status":"downloading|failed|success","error"}`; boot `{"event":"boot","status":"online","version":"1.5.0"}` |
| `machines/{sn}/cmd` | server → device | `{"action":"REMOTE_START","op_id","pulses","width","delay","type","price"}`; `{"action":"CONFIG",...pulse/coin settings}` (**retained**); `{"action":"REBOOT"}`; plain text `UPDATE:<url>` (OTA) |

Server publishes with QoS 0. The bridge subscribes with QoS 0 on one connection with a clean session.

---

## 4. End-to-end flows

### 4.1 Telemetry / heartbeat
1. ESP32 publishes to `machines/{sn}/telemetry` every 60 s (and once per coin).
2. `MqttBridge` logs the message (`Log::info`), JSON-decodes it, and dispatches `ProcessMachineMessage` to Horizon.
3. The job writes Redis: `machine:status:{sn}` (SETEX 600 s), `machine_heartbeats` hash (`HSET sn ts`), `machine:last_payload:{sn}`.
4. The job SELECTs `device_has_outlet` and only UPDATEs the DB if coins > 0 or the status changed (`current_coins`/`lifetime_coins` += coins, `last_seen_at`, `availability`, `last_reboot_at`).
5. `DeviceOutlet::is_online` = `Redis::exists("machine:status:{sn}")`. The `last_seen_at` accessor prefers the Redis heartbeat.

### 4.2 Watchdog
`app:machine-watchdog` runs every minute. It finds `device_has_outlet.status='online' AND last_seen_at < now()-15min` (the **DB column**), marks those rows offline, and sends one Telegram alert per outlet.

### 4.3 Customer payment → machine credit (pulse-controlled, not API-controlled)
1. The customer scans the device QR (`/device/{serial}`), then picks a mode (washer_cold … dryer_hi). The server computes the price from the `devices` columns and never trusts a client amount.
2. Gateway path: `initiateDevicePayment` (logged-in) or `initiateQRPayment` (guest) creates a `device_transactions` row (`initiated`, `order_id` `ORD-M-YmdHis-{customerId|guest}`) and renders the Fiuu form (`vcode = md5(amount+merchant_id+order_id+verify_key)`).
3. Fiuu calls back on `/customer/customer/payment/callback` or `/guest/payment/callback`. The handler verifies `skey`; on `00` it sets the row to `paid` and calls `dispatchMachineStart()`.
4. E-wallet path: `payWithEwallet` checks `credit_balance`, decrements it, creates the `device_transactions` row (`paid`, provider `ewallet`), then calls `dispatchMachineStart()`.
5. `dispatchMachineStart()` computes `pulses = ceil(amount / pulse_price)` and generates `op_id` (UUID). It dispatches the `SendMqttCommand(REMOTE_START)` job, then sets the transaction to `activated` with `meta.op_id`.
6. The job publishes to `machines/{sn}/cmd` (QoS 0). The firmware de-dupes on `op_id` against the *last completed* op only, drives `COIN_OUTPUT_PIN` (GPIO27 in v1.5.0; **GPIO4 since v1.5.3**) for N pulses without blocking, and acks `received` then `completed`.
7. The bridge stores the ack at `start_ack:{op_id}` (SETEX 120 s). The browser polls `/payment/{transaction}/ack-status`; the admin app polls `/api/technician/start-ack/{opId}` every 2 s.
8. The customer then presses the machine's own Start button (the pulses act like inserted coins).

### 4.4 E-wallet top-up
`EwalletController::initiateTopup` uses order `ORD-YmdHis-{customerId}`. The Fiuu `/customer/gateway/callback` runs `GatewayController::callback`, which calls `EwalletService::creditTopupWithPackage()` (row lock on the gateway txn, credit + package bonus to `bonus_balance`). The return URL is `/customer/gateway/return`.

### 4.5 Config / reboot / OTA
- CONFIG is triggered from the web (`device_outlets/{id}/send-config`, device update) or the app (`/technician/devices/{sn}/parameters|send-config`). It publishes **retained** CONFIG; the device saves it to Preferences and re-applies pin modes and the interrupt edge (FALLING if the coin signal idles high, RISING otherwise).
- REBOOT sets the DB status to `rebooting` and publishes `{"action":"REBOOT"}`.
- UPDATE publishes `UPDATE:<url>`; the device calls `httpUpdate.update()` and reports `ota_status` on `/status`. OTA is ignored while a pulse sequence is running.

### 4.6 Wi-Fi provisioning (BLE)
The device always advertises `LB-{serial}` with service `d804b643-…12345` (write `…12346`, status notify `…12347`; Wi-Fi scan `…12348` exists **only in firmware v1.5.4+**). The admin app scans the QR, connects over BLE, can ask the device to scan Wi-Fi, and writes `{ssid,password}`. Credentials persist in Preferences.

### 4.7 Physical coins
The ISR on GPIO14 (debounce = `coin_signal_sensitivity`) increments `pendingPulses`. The pending count is persisted to flash (`coin-vault`) and uploaded one MQTT message per coin.

---

## 5. Redis keys

| Key | Type / TTL | Writer → Reader |
|---|---|---|
| `machine:status:{sn}` | string, 600 s | ProcessMachineMessage → `is_online` |
| `machine_heartbeats` | hash sn→unix ts | ProcessMachineMessage → `last_seen_at` accessor |
| `machine:last_payload:{sn}` | string, no TTL | ProcessMachineMessage → UI tooltip |
| `machine_last_reboot` | hash | ProcessMachineMessage (moved to DB on next DB write) |
| `start_ack:{op_id}` | string, 120 s | MqttBridge → ackStatus / startAck |
| Horizon keys | prefix `{app}_horizon:` | Horizon |

---

## 6. Horizon configuration

- `environments.production`: `supervisor-mqtt` (queues `mqtt-commands`, `default`, 5–20 procs, timeout 30) and `supervisor-heavy` (`reports`, `notifications`, 5 procs).
- `environments.local`: `supervisor-1`, maxProcesses 3.
- **The live `.env` has `APP_ENV=local`**, so only the local supervisor (≤3 workers on `default`) is running. No job calls `onQueue('mqtt-commands')`, so everything goes to `default`.

---

## 7. Flutter admin app (`lb_admin_app`)

- Tabs: **Dashboard** (outlet/device counts, online ratio), **Outlet** (outlets, then devices with live status), **Device Install** (Quick Start, Config Network over BLE, Config Parameter), **Profile**.
- Auth: `POST /api/technician/login` returns a Sanctum token, stored in `flutter_secure_storage` and sent as a Bearer token.
- Endpoints: `/technician/dashboard|profile|outlets|outlets/{id}/devices|devices/{sn}|devices/{sn}/parameters (PUT)|devices/{sn}/send-config|devices/{sn}/start|start-ack/{opId}`.
- The backend enforces outlet scoping (`User::canAccessOutlet()` / `canAccessAllOutlets()`) and permissions (`devices_outlet.manage`). The app sends a price, and the server computes pulses and checks `max_vend_price`.
- Known gaps (from its README): no token-expiry handling, no pagination, no offline cache, no tests. The base URL is hard-coded in `lib/services/app_config.dart`.

---

## 8. Findings for the 8,000-device rollout (prioritised)

Severity: 🔴 must fix before scaling · 🟠 fix during rollout · 🟡 hygiene.

### 🔴 F1. Account takeover through the payment return URLs ✅ patched
`PaymentController::return` and `GatewayController::return` call `Auth::guard('customer')->loginUsingId($transaction->customer_id)` **before and regardless of** the signature check. Order IDs are predictable (`ORD-YmdHis-{customerId}`), so anyone can hit `/customer/gateway/return?orderid=…` and get logged in as that customer, then spend their wallet.
**Fix, step by step:**
1. Remove the `loginUsingId` calls.
2. Before payment, store the transaction id in the customer's own session. On return, show the result page only if the session matches. Otherwise show a neutral "check your history" page.
3. Make order IDs unguessable: `ORD-{ULID}`. Also stops guest ID collisions (see F6).
4. Invalidate existing sessions after deploying (`php artisan session:flush` or rotate `APP_KEY`, which also logs everyone out).

### 🔴 F2. Duplicate Fiuu callbacks give a free machine credit ✅ patched
Fiuu delivers the result more than once: the callback, the notification, and retries while `nbcb` is not acknowledged. `PaymentController::callback` has no idempotency check. Every `00` callback sets the row to `paid` again and calls `dispatchMachineStart()` with a **new op_id**, so the firmware's op_id de-dupe can't catch it.
**Fix:**
1. Wrap the handler in `DB::transaction` and `lockForUpdate()` the `device_transactions` row.
2. If the status is already `paid`, `activated` or `completed`, return `CBTOKEN:MPSTATOK` with no other side effects.
3. Also check that `amount` equals the stored `amount` (and the currency) before marking it paid.
4. Generate the op_id **once per transaction** (for example the transaction ULID), so even a re-dispatch is de-duped by the firmware.

### 🔴 F3. Duplicate top-up callbacks credit the wallet twice ✅ patched
In `GatewayController::callback`, `$pg->update(['status' => 'initiated'])` runs **before** `creditTopupWithPackage()`. The service's idempotency guard (`status === 'paid' && ledger exists`) therefore never matches on a repeat callback, and the wallet is credited again.
**Fix:**
1. Delete the pre-update.
2. In the service, after `lockForUpdate`, return early if the status is `paid` (and/or a ledger row exists with `reference = order_id`).
3. Add a unique index on `ewallet_transactions(reference, transaction_type)` as a database-level backstop.

### 🔴 F4. Wallet payment race and missing ledger ✅ patched
`payWithEwallet` checks the balance, then runs a separate `decrement()` with no lock. Concurrent taps can overspend and drive the balance negative. It also writes no `ewallet_transactions` entry and ignores `bonus_balance`. `EwalletService::debitSpend()` already exists but isn't used, and it writes `type` while the column is `transaction_type`.
**Fix:**
1. Fix `debitSpend` to use `transaction_type` and `remaining_balance`.
2. Call it inside a `DB::transaction` that also creates the `device_transactions` row.
3. Dispatch the MQTT job with `->afterCommit()`.

### 🔴 F5. Watchdog flaps every healthy device offline every ~16 minutes
Heartbeats never update the DB `last_seen_at` (to save IOPS). The watchdog reads the **DB** column. So 15 minutes after any device comes online, the watchdog marks it offline and sends a Telegram alert. The next heartbeat flips it back online with a DB write. At 8,000 devices that is roughly 750k false transitions a day, Telegram rate limits (≈20 messages/min per group), and constant RDS writes.
**Fix:**
1. Make the watchdog read `HGETALL machine_heartbeats` (or `ZRANGEBYSCORE` on a sorted set, see F8) and pick serials whose `ts < now-180`.
2. Only devices that are actually stale get the DB update and the alert.
3. Batch alerts: one digest per run, and only for *transitions*.
4. Optionally flush `last_seen_at` to the DB in bulk every 5–10 minutes from Redis.

### 🔴 F6. Offline devices still look online, and paid starts can be lost silently
- `is_online` only checks that `machine:status:{sn}` **exists**. The LWT `{"status":"offline"}` *writes* that key, so a device that just went offline shows as **online for 10 minutes** and payments are accepted.
- The LWT is published **retained**, and heartbeats are not retained, so the retained "offline" is never cleared. Every bridge restart replays an "offline" for every device that has ever dropped, causing mass DB flips.
- REMOTE_START uses QoS 0 with a clean session. If the device is unreachable, the command is dropped, but the transaction is already marked `activated`. Nothing reconciles a paid transaction that never gets a `completed` ack.

**Fix:**
1. Change `is_online` to `Redis::get(key) === 'online'`, and lower the heartbeat TTL to ~150 s (2.5× the heartbeat interval).
2. Firmware: publish LWT and online status to a dedicated `machines/{sn}/presence` topic, retained, with `online` published (retained) on connect. Or simply don't retain the LWT.
3. Set the transaction to `activated` only when the ack is `received`/`completed`. Add a scheduled job: any `paid` transaction without `completed` after 2 minutes is retried (same op_id) or flagged for auto-refund to the e-wallet, plus an alert.
4. Use QoS 1 for `cmd` and acks. Firmware 1.6 can then subscribe with QoS 1 (PubSubClient supports QoS 1 subscribe).
5. Use 422 errors (not a redirect) for "machine offline" so the UI is clear.

### 🔴 F7. Firmware reboot-loops when the broker is down; one shared build per device
- `reconnect()` loops forever with `delay(5000)` and never feeds the task watchdog (5 s timeout). If the broker or Wi-Fi is down, every device reboots every ~5 s. It never re-calls `setup_wifi()` if Wi-Fi dropped. When the broker comes back, 8,000 devices reconnect at once (thundering herd).
- Serial, MQTT username/password, the default Wi-Fi password and the broker IP are **compiled in**. 8,000 units would need 8,000 builds, and one leaked binary exposes the shared password.
- The boot event is published in `setup()` *before* MQTT connects, so it's never sent (reboot detection never fires). It's also sent on `/status`, where the bridge's fall-through branch would overwrite `ota_status` with null.

**Fix (firmware v1.6):**
1. Make `reconnect()` non-blocking: one attempt per loop iteration, exponential backoff with jitter (1 s → 60 s, ±30%), feed the WDT, reconnect Wi-Fi when `WiFi.status() != WL_CONNECTED`.
2. Derive the serial from the eFuse MAC (or burn it into NVS at the factory). Give each device its own credentials (EMQX per-client password or X.509 certs) and an EMQX ACL so a device can only pub/sub `machines/{its-sn}/#`.
3. Move to TLS on 8883, and use HTTPS OTA with certificate pinning.
4. Send boot after connect, on `telemetry` or with `event` handled explicitly in the bridge.

### 🟠 F8. Ingest throughput: one PHP bridge, one job per heartbeat
At 8,000 devices × 1 heartbeat/60 s you get ≈133 msg/s steady, with bursts after an outage of 8,000 messages in a few seconds. Each message costs a `Log::info` line (~11.5M lines a day, plus php-mqtt client logging, which is enabled), a Horizon job, a DB SELECT, and three Redis writes, all on ≤3 workers (F9).
**Fix:**
1. In the bridge, do the Redis writes **inline** (pipeline) for heartbeats. Only dispatch a job when `coins > 0` or the status actually changes (compare against Redis, not the DB).
2. Remove the per-message `Log::info` and set `MQTT_ENABLE_LOGGING=false`.
3. Use a `machine_last_seen` sorted set (`ZADD ts sn`) to make the watchdog O(log n).
4. Run 2–3 bridge replicas with **EMQX shared subscriptions** (`$share/lbpay/machines/+/telemetry`), each with a unique `MQTT_CLIENT_ID`, `use_clean_session=false` and auto-reconnect enabled. Today auto-reconnect is off, so the bridge dies on a broker blip and relies on Docker restart.
5. Stretch the heartbeat to 120–300 s for idle devices. Presence comes from the LWT, so a slower heartbeat costs little.
6. Prefer EMQX Rule Engine → Redis/HTTP for pure heartbeats if you later want to remove PHP from the hot path.

### 🟠 F9. Production is running as `APP_ENV=local`, `APP_DEBUG=true`
- Horizon uses the *local* supervisor (max 3 processes). The production supervisors never start.
- `APP_DEBUG=true` exposes stack traces, environment details and SQL to the public on any error.
- `.env` sets `QUEUE_CONNECTION` twice (database, then redis) and `CACHE_STORE=database` (`CACHE_DRIVER` is ignored in Laravel 11+), so cache and locks hit RDS. `SESSION_DRIVER=database`.

**Fix:**
1. Set `APP_ENV=production`, `APP_DEBUG=false` and `LOG_LEVEL=warning`.
2. Use `CACHE_STORE=redis` and `SESSION_DRIVER=redis`, with a single `QUEUE_CONNECTION=redis`.
3. Use `->onQueue('mqtt-commands')` for `SendMqttCommand`, and the `notifications` queue for Telegram.
4. Run `php artisan config:cache route:cache event:cache view:cache`.
5. Run `horizon:terminate` on each deploy.
6. `OutletObserver` calls `Cache::flush()`, which also wipes Redis keys once the cache moves to Redis if the same connection is shared. Use a separate Redis DB/connection for cache, or tags/specific keys.

### 🟠 F10. Secrets and exposure
- The archive includes `.env` (RDS, mail, AWS keys, Telegram token, MQTT password, IoT API key), `.key/*` (AWS IoT device private keys) and `nginx_data` (NPM database and keys). Firmware sources contain Wi-Fi and MQTT passwords. **Rotate all of them** and keep them out of tarballs and git. Use Docker secrets or AWS SSM.
- Redis is published on `0.0.0.0:6379`, apparently without a password. Bind it to the Docker network only (remove `ports:`) and set `requirepass`.
- NPM admin port 81 is public; restrict it with the security group or a VPN. Put phpMyAdmin behind an NPM access list, or remove it.
- `/api/technician/login` has no throttle. Add `throttle:5,1` and a token `expiration` in `config/sanctum.php`.
- `VerifyDeviceApiKey` uses one fleet-wide key with `!==`. Use `hash_equals` and per-device keys, or retire the HTTP status endpoints (MQTT supersedes them). `bulkUpdate` has a typo: `exists::devices` breaks validation.

### 🟠 F11. Docker / runtime layout
- Every service bind-mounts the source tree and runs Apache (mod_php). At scale, use built images (no bind mount) and PHP-FPM + Nginx, or Octane/FrankenPHP for the web container.
- `restart: always` without healthchecks: add healthchecks for the bridge (e.g. a heartbeat key in Redis written every loop) and for Horizon (`horizon:status`).
- Redis has a 1 GB limit and no persistence config. Set `appendonly yes` because Horizon and acks live there, and set `maxmemory-policy noeviction` for queue safety.
- MySQL on RDS: add indexes on `device_transactions(status, created_at)`, `device_has_outlet(status, last_seen_at)` and `ewallet_transactions(reference)`.

### 🟡 F12. Smaller correctness items
- Guest order IDs `ORD-M-{YmdHis}-guest` collide when two guests pay in the same second (`order_id` is unique, so the second gets a 500 error). Fixed by the ULID in F1.
- `returnQRPayment` has signature verification commented out. It's display-only, but it shows "success" from a forged query string.
- In `return()`/`returnQRPayment()`, the `$transaction->id` dereference in the failure branch crashes when the order isn't found.
- The firmware's op_id de-dupe only remembers the *last completed* op. A redelivered op that is currently mid-pulse is answered `busy` (fine), but an older op_id would be re-executed. Keep a small ring buffer (last 8 op_ids) in RTC memory/NVS.
- The admin app README requires firmware **v1.5.4+** for BLE Wi-Fi scan, but the shipped firmware is **v1.5.0** (no `…12348` characteristic).
- The ack poll in the app has no overall timeout. Stop after ~60 s and show "no response, check the machine".
- Vending machines: `MODE_PRICE_COLUMNS` and `machine_type` only know Washer/Dryer. Anything that isn't a Washer is forced to `dryer_*` modes, so vending needs its own type and price model (e.g. `machine_type=Vending`, `vend_price` or per-slot prices).

### 🟠 F13. Found while fixing F1–F4
- **`device_has_outlet.status` is `enum('Online','Offline')`** in the migration, but the code writes `online`, `offline` and `rebooting`. If production still has that enum:
  - `ProcessMachineMessage` compares `'Online' !== 'online'`, which is always true, so **every heartbeat writes to RDS** (this makes F8 much worse).
  - REBOOT's `'rebooting'` is rejected in MySQL strict mode.
  Check with `SHOW COLUMNS FROM device_has_outlet LIKE 'status'`. Fix it together with F5/F8: a varchar column, or case-insensitive compares.
- `ewallet_transactions.user_id` had a foreign key to staff `users` but stores customer IDs. Patch migration `2026_10_14_000002` re-points it to `customers`.
- The Fiuu verify key was hard-coded in `device-form.blade.php` and `topup-form.blade.php`, and those forms posted the Laravel CSRF token to Fiuu. The patch removes both. **The key still needs rotating.**
- The user's git repo tracks `nginx_data/` (the NPM database and keys).
- `AppServiceProvider` runs `MailServer::first()` on every request. The patch makes it safe on a fresh DB, but the query should also be cached.
- Routes inside `Route::prefix('customer')` that also start with `/customer/...` are doubled: `/customer/customer/payment/ewallet`, `/customer/customer/device/{id}/start`, `/customer/customer/payment/callback|return`. This works, but keep it in mind when configuring Fiuu URLs and writing tests.

### 🔴 F14. RM 2 remote start gave the machine nothing (fixed 2026-10-16)
- **Firmware v1.5.4** (latest at the time, COIN_OUTPUT_PIN = GPIO4, BLE Wi-Fi scan) had `MAX_PULSES_PER_START = 100`. With `pulse_price` = RM 0.01 (RM 1 = 100 pulses, the user's setting), any start above RM 1 was rejected (`too_many_pulses`), while the backend marked the payment activated.
- **Fix:** firmware **v1.5.5**:
  - `cfg.maxPulses` (default 1000, ceiling 2000, set via CONFIG `max_pulses`)
  - REMOTE_START honours the `width`/`delay` it is sent
  - acks carry `pulses`/`sent`/limit
- **Backend:**
  - `config/lbpay.php` `max_pulses_per_start` (env `LBPAY_MAX_PULSES_PER_START`)
  - `Device::pulsesFor()` / `maxPulsesPerStart()` / `pulseLimitProblem()`
  - refuses before charging; parameter saves validated
  - technician start-ack returns `error`/`pulses`/`sent`
- **Rollout rule:** keep `LBPAY_MAX_PULSES_PER_START=100` until every device runs v1.5.5.
- **Open design point:** at RM 0.01/pulse a RM 10 start is 1000 pulses (≈140–300 s). Recommend checking whether the machine's coin input supports a larger value per pulse.

## Change log
- **2026-10-14:** F1–F4 patch:
  - New: `App\Support\OrderId` (ULID order IDs `ORD-M|W|T-…`), `App\Support\Money` (integer-sen maths), `App\Services\FiuuSignature` (vcode + `hash_equals` skey check), `App\Services\DevicePaymentSettlement` (locked, idempotent settlement with amount check; new status `review`), `App\Services\MachineStartService` (one op_id per order, `afterCommit` dispatch).
  - `EwalletService`: `settleTopup()` and `spendForDevice()` replace `creditTopupWithPackage()` and `debitSpend()`. Spending uses credit first, then bonus, writes a `deduction` ledger row, and has a 15 s double-tap guard.
  - Return URLs no longer log anyone in. Result pages need a signed link (2 h) or the owner.
  - Migrations: unique `(reference, transaction_type)` on `ewallet_transactions` (aborts and lists past duplicates), and the FK fix.
  - Tests: `tests/Feature/PaymentSecurityTest.php`, 15 tests.

---

## 9. Capacity cheat-sheet (8,000 devices)

| Item | Current | Recommended |
|---|---|---|
| Heartbeat | 60 s → 133 msg/s, 11.5M/day | 120–300 s + LWT presence |
| Jobs per heartbeat | 1 (always) | 0 (inline Redis); job only on coin/status change |
| Bridge | 1 PHP process, QoS 0, clean session, no auto-reconnect | 2–3 replicas, `$share/` subscriptions, persistent sessions |
| Horizon workers | 3 (local env) | `supervisor-mqtt` 5–20, `supervisor-heavy` 5 |
| Watchdog | DB `last_seen_at` (flaps) | Redis ZSET, alert on transitions only |
| Online check | key exists (TTL 600 s) | value == online, TTL ~150 s |
| Broker | EMQX, 1883, shared creds | EMQX 8883 TLS, per-device creds + ACL; a single EMQX node handles 8k connections easily, but cluster 2 nodes for HA |

---

## 10. Recommended rollout order

1. **Security and money (week 1):** F1, F2, F3, F4, F10 secrets rotation, F9 env.
2. **Presence and reliability (week 2):** F5, F6 (backend side), F8 bridge changes.
3. **Firmware v1.6 (week 2–4):** F7 plus QoS 1, presence topic, op_id ring buffer, BLE scan characteristic. Pilot on 50 devices, then roll out OTA in waves of 500 with `ota_status` monitoring.
4. **Scale infra:** built images, FPM/Octane, Redis AOF, a second EMQX node, load test with an MQTT simulator (e.g. `emqtt-bench` with 8,000 clients) before going past ~1,000 live units.
5. **Vending support:** machine type and pricing model (F12).

---

## 11. File map (quick lookup)

- MQTT ingest: `app/Console/Commands/MqttBridge.php`
- Telemetry job: `app/Jobs/ProcessMachineMessage.php`
- Commands to devices: `app/Jobs/SendMqttCommand.php`
- Watchdog: `app/Console/Commands/MachineWatchdog.php` (scheduled in `routes/console.php`)
- Payments: `app/Http/Controllers/PaymentController.php` (device), `GatewayController.php` (top-up), `app/Services/EwalletService.php`
- Technician API: `app/Http/Controllers/Api/Technician*Controller.php`, `routes/api.php`
- Web routes: `routes/web.php`
- Horizon: `config/horizon.php`; MQTT: `config/mqtt-client.php`
- Docker: `docker-compose.yml`, `Dockerfile`, `docker/apache/000-default.conf`
- Firmware: `iot_firmware/lbpaylinker_v1_5.ino` (latest), v1.2–1.4 history, AWS IoT leftovers (`esp32_aws_led.ino`, `lambda_function.py`, `provision_thing.sh`)
- Flutter app: `admin_app/lib/services/api_service.dart`, `ble_provisioning_service.dart`, `screens/*`
- **2026-10-16:** pulse-limit fix (F14):
  - firmware v1.5.5
  - backend incremental patch on top of F1–F4, or cumulative patch on the snapshot
  - tests: `PulseLimitTest` (6), with the shared fixture in `PaymentTestCase`
  - a host-side firmware simulation confirmed v1.5.4 sends 0 pulses for 200 and v1.5.5 sends 200
