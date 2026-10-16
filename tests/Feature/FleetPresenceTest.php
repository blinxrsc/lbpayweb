<?php

namespace Tests\Feature;

use App\Jobs\ProcessMachineMessage;
use App\Jobs\RecordStartAck;
use App\Jobs\SendMqttCommand;
use App\Models\DeviceOutlet;
use App\Models\DeviceTransaction;
use App\Services\MachineMessageHandler;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;

/**
 * F5 watchdog, F6 presence + start reconciliation, F8 ingest, F13 status column.
 * Fixture (PaymentTestCase): device TESTSN0001 at an outlet, pulse_price RM1.
 */
class FleetPresenceTest extends PaymentTestCase
{
    private MachineMessageHandler $h;
    private string $sn = 'TESTSN0001';

    protected function setUp(): void
    {
        parent::setUp();
        $this->h = app(MachineMessageHandler::class);
        $this->resetRedis();
        config(['services.telegram.bot_token' => 'T', 'services.telegram.admin_chat_id' => '1']);
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);
    }

    protected function tearDown(): void
    {
        $this->resetRedis();
        parent::tearDown();
    }

    private function resetRedis(): void
    {
        Redis::del(DeviceOutlet::presenceKey($this->sn), "machine:last_payload:{$this->sn}");
        Redis::hdel(MachineMessageHandler::HEARTBEATS, $this->sn);
        Redis::zrem(MachineMessageHandler::LAST_SEEN, $this->sn);
    }

    private function telemetry(array $p, bool $retained = false): string
    {
        return $this->h->handle("machines/{$this->sn}/telemetry", json_encode($p), $retained);
    }

    private function outlet(): DeviceOutlet
    {
        return DeviceOutlet::where('device_serial_number', $this->sn)->first();
    }

    // ───────────── F8: ingest ─────────────

    public function test_plain_heartbeats_create_no_jobs_after_the_first(): void
    {
        $this->assertSame('came_online', $this->telemetry(['status' => 'online', 'coins' => 0]));
        $this->assertSame('heartbeat', $this->telemetry(['status' => 'online', 'coins' => 0]));
        $this->assertSame('heartbeat', $this->telemetry(['status' => 'online', 'coins' => 0]));

        Queue::assertPushed(ProcessMachineMessage::class, 1);
        $this->assertTrue($this->outlet()->is_online);
        $this->assertNotFalse(Redis::zscore(MachineMessageHandler::LAST_SEEN, $this->sn));
        $this->assertSame((int) config('lbpay.presence_ttl_seconds'), Redis::ttl(DeviceOutlet::presenceKey($this->sn)));
    }

    public function test_empty_retained_delete_is_ignored_quietly(): void
    {
        $this->assertSame('ignored_empty', $this->h->handle("machines/{$this->sn}/telemetry", '', true));
        $this->assertSame('ignored_empty', $this->h->handle("machines/{$this->sn}/telemetry", ''));
        Queue::assertNothingPushed();
    }

    public function test_coin_message_queues_a_job_with_the_coins(): void
    {
        $this->telemetry(['status' => 'online', 'coins' => 0]);
        $this->assertSame('coins', $this->telemetry(['status' => 'online', 'coins' => 1]));

        Queue::assertPushed(ProcessMachineMessage::class, fn ($j) => $j->payload['coins'] === 1);
    }

    public function test_retained_old_lwt_on_telemetry_is_ignored(): void
    {
        $this->telemetry(['status' => 'online']);
        $this->assertSame('ignored_retained', $this->telemetry(['status' => 'offline'], retained: true));
        $this->assertTrue($this->outlet()->is_online);
    }

    public function test_live_offline_message_makes_device_not_payable(): void
    {
        $this->telemetry(['status' => 'online']);
        $this->assertSame('went_offline', $this->telemetry(['status' => 'offline']));

        // F6: used to stay "online" for 10 minutes because the key existed.
        $this->assertFalse($this->outlet()->is_online);
        Queue::assertPushed(ProcessMachineMessage::class, fn ($j) => $j->payload['status'] === 'offline');
    }

    public function test_presence_topic_online_and_offline(): void
    {
        $this->assertSame('came_online', $this->h->handle("machines/{$this->sn}/presence", '{"status":"online"}', true));
        $this->assertTrue($this->outlet()->is_online);
        $this->assertSame('went_offline', $this->h->handle("machines/{$this->sn}/presence", '{"status":"offline"}', true));
        $this->assertFalse($this->outlet()->is_online);
    }

    public function test_boot_event_records_version_and_reboot(): void
    {
        $this->assertSame('boot', $this->h->handle("machines/{$this->sn}/status", '{"event":"boot","status":"online","version":"1.5.6"}'));

        Queue::assertPushed(ProcessMachineMessage::class, fn ($j) => ($j->payload['event'] ?? null) === 'boot');
        (new ProcessMachineMessage($this->sn, ['status' => 'online', 'event' => 'boot', 'version' => '1.5.6']))->handle();

        $this->assertSame('1.5.6', DB::table('devices')->where('serial_number', $this->sn)->value('version'));
        $this->assertNotNull(DB::table('device_has_outlet')->where('device_serial_number', $this->sn)->value('last_reboot_at'));
    }

    public function test_unknown_status_message_no_longer_wipes_ota_status(): void
    {
        DB::table('devices')->where('serial_number', $this->sn)->update(['ota_status' => 'success']);
        $this->assertSame('ignored_status', $this->h->handle("machines/{$this->sn}/status", '{"something":"else"}'));
        $this->assertSame('success', DB::table('devices')->where('serial_number', $this->sn)->value('ota_status'));
    }

    // ───────────── F13: status column ─────────────

    public function test_legacy_capitalised_status_does_not_cause_a_db_write_per_heartbeat(): void
    {
        DB::table('device_has_outlet')->where('device_serial_number', $this->sn)->update(['status' => 'Online', 'updated_at' => '2026-01-01 00:00:00']);

        (new ProcessMachineMessage($this->sn, ['status' => 'online', 'coins' => 0]))->handle();

        $this->assertSame('2026-01-01 00:00:00', DB::table('device_has_outlet')->where('device_serial_number', $this->sn)->value('updated_at'));
    }

    public function test_status_column_accepts_rebooting_and_faulty_is_never_overwritten(): void
    {
        DB::table('device_has_outlet')->where('device_serial_number', $this->sn)->update(['status' => 'faulty']);

        (new ProcessMachineMessage($this->sn, ['status' => 'online', 'coins' => 2]))->handle();

        $row = DB::table('device_has_outlet')->where('device_serial_number', $this->sn)->first();
        $this->assertSame('faulty', $row->status);     // maintenance flag kept
        $this->assertEquals(2, $row->current_coins);    // coins still counted

        DB::table('device_has_outlet')->where('device_serial_number', $this->sn)->update(['status' => 'rebooting']); // was invalid for the enum
        $this->assertSame('rebooting', $this->outlet()->status);
    }

    // ───────────── F5: watchdog ─────────────

    public function test_watchdog_does_not_flap_a_device_that_keeps_sending_heartbeats(): void
    {
        DB::table('device_has_outlet')->update(['status' => 'online', 'last_seen_at' => now()->subHour()]); // old DB value
        $this->telemetry(['status' => 'online']); // fresh heartbeat in Redis

        $this->artisan('app:machine-watchdog')->assertSuccessful();

        $this->assertSame('online', $this->outlet()->status);
        Http::assertNothingSent();
    }

    public function test_watchdog_marks_silent_device_offline_and_alerts_once(): void
    {
        DB::table('device_has_outlet')->update(['status' => 'online']);
        $this->telemetry(['status' => 'online']);
        Redis::zadd(MachineMessageHandler::LAST_SEEN, now()->subMinutes(10)->timestamp, $this->sn);

        $this->artisan('app:machine-watchdog')->assertSuccessful();
        $this->artisan('app:machine-watchdog')->assertSuccessful();

        $this->assertSame('offline', $this->outlet()->status);
        $this->assertFalse($this->outlet()->is_online);
        Http::assertSentCount(1);
        Http::assertSent(fn ($r) => str_contains($r['text'], 'TESTSN0001') && str_contains($r['text'], 'Outlet 1'));
    }

    // ───────────── F6: paid-start tracking & reconciliation ─────────────

    private function activatedStart(int $startedSecondsAgo, array $meta = []): DeviceTransaction
    {
        $txn = DeviceTransaction::create([
            'customer_id' => $this->customer->id, 'device_outlet_id' => $this->deviceOutletId,
            'amount' => '5.00', 'currency' => 'MYR', 'status' => 'paid', 'provider' => 'ewallet',
            'order_id' => 'ORD-W-' . uniqid(), 'meta' => ['mode' => 'washer_cold'],
        ]);
        app(\App\Services\MachineStartService::class)->dispatchForTransaction($txn);
        $txn->refresh();
        $txn->update(['meta' => array_merge($txn->meta, ['started_at' => now()->subSeconds($startedSecondsAgo)->toIso8601String()], $meta)]);
        return $txn->fresh();
    }

    public function test_device_acks_are_recorded_on_the_transaction(): void
    {
        $txn = $this->activatedStart(5);
        $op = $txn->meta['op_id'];

        $this->h->handle("machines/{$this->sn}/status", json_encode(['event' => 'start_ack', 'op_id' => $op, 'status' => 'received', 'pulses' => 5]));
        $this->h->handle("machines/{$this->sn}/status", json_encode(['event' => 'start_ack', 'op_id' => $op, 'status' => 'completed', 'pulses' => 5, 'sent' => 5]));

        Queue::assertPushed(RecordStartAck::class, 2);
        (new RecordStartAck($txn->id, ['op_id' => $op, 'status' => 'received']))->handle();
        (new RecordStartAck($txn->id, ['op_id' => $op, 'status' => 'completed', 'sent' => 5]))->handle();

        $txn->refresh();
        $this->assertSame('completed', $txn->status);
        $this->assertSame(5, $txn->meta['pulses_sent']);
        $this->assertNotEmpty($txn->meta['ack_received_at']);
    }

    public function test_rejected_start_goes_to_review(): void
    {
        $txn = $this->activatedStart(5);
        (new RecordStartAck($txn->id, ['op_id' => $txn->meta['op_id'], 'status' => 'rejected', 'error' => 'too_many_pulses:max=100']))->handle();

        $txn->refresh();
        $this->assertSame('review', $txn->status);
        $this->assertSame('too_many_pulses:max=100', $txn->meta['ack_error']);
    }

    public function test_unacknowledged_start_is_resent_once_with_the_same_op_id(): void
    {
        DB::table('devices')->where('serial_number', $this->sn)->update(['version' => '1.5.6']);
        $this->telemetry(['status' => 'online']);
        $txn = $this->activatedStart(120);
        $op = $txn->meta['op_id'];

        $this->artisan('app:reconcile-starts')->assertSuccessful();
        $this->artisan('app:reconcile-starts')->assertSuccessful();

        // 1 original + exactly 1 retry, both with the same op_id.
        Queue::assertPushed(SendMqttCommand::class, 2);
        Queue::assertPushed(SendMqttCommand::class, fn ($j) => $j->payload['op_id'] === $op);
        Queue::assertNotPushed(SendMqttCommand::class, fn ($j) => $j->payload['op_id'] !== $op);
        $this->assertSame(1, $txn->fresh()->meta['retry_count']);
    }

    public function test_older_firmware_is_never_auto_resent(): void
    {
        DB::table('devices')->where('serial_number', $this->sn)->update(['version' => '1.5.5']);
        $this->telemetry(['status' => 'online']);
        $this->activatedStart(120);

        $this->artisan('app:reconcile-starts')->assertSuccessful();

        Queue::assertPushed(SendMqttCommand::class, 1); // only the original — goes to review later instead
    }

    public function test_received_start_is_never_auto_resent(): void
    {
        DB::table('devices')->where('serial_number', $this->sn)->update(['version' => '1.5.6']);
        $this->telemetry(['status' => 'online']);
        $this->activatedStart(120, ['ack_received_at' => now()->toIso8601String()]);

        $this->artisan('app:reconcile-starts')->assertSuccessful();

        Queue::assertPushed(SendMqttCommand::class, 1); // only the original
    }

    public function test_stuck_start_is_flagged_for_review_and_reported(): void
    {
        $txn = $this->activatedStart(700);

        $this->artisan('app:reconcile-starts')->assertSuccessful();

        $this->assertSame('review', $txn->fresh()->status);
        $this->assertSame('no_ack_from_device', $txn->fresh()->meta['review_reason']);
        Http::assertSent(fn ($r) => str_contains($r['text'], $txn->order_id));
    }

    public function test_old_activated_rows_without_tracking_are_left_alone(): void
    {
        $legacy = DeviceTransaction::create([
            'customer_id' => $this->customer->id, 'device_outlet_id' => $this->deviceOutletId,
            'amount' => '5.00', 'currency' => 'MYR', 'status' => 'activated', 'provider' => 'fiuu',
            'order_id' => 'ORD-M-LEGACY', 'meta' => ['op_id' => 'x'],
        ]);

        $this->artisan('app:reconcile-starts')->assertSuccessful();

        $this->assertSame('activated', $legacy->fresh()->status);
        Http::assertNothingSent();
    }

    public function test_customer_page_shows_duplicate_ignored_as_completed(): void
    {
        $txn = $this->activatedStart(5);
        Redis::setex("start_ack:{$txn->meta['op_id']}", 60, json_encode(['status' => 'duplicate_ignored']));

        $this->getJson("/payment/{$txn->id}/ack-status")->assertJson(['status' => 'completed']);
    }
}
