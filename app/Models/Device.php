<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Device extends Model
{
    protected $fillable = [
        'serial_number',
        'model',
        'version',
        'order_number',
        'purchase_date',
        'supplier_id',
        'purchase_cost',
        'washer_cold_price','washer_warm_price','washer_hot_price',
        'dryer_low_price','dryer_med_price','dryer_hi_price',
        'pulse_price','pulse_add_min','pulse_width','pulse_delay','coin_signal_width',
        'max_vend_price','pulse_pull_up','coin_signal_idle_high','coin_signal_sensitivity','pulse_active_low',
        'status',
        'outlet_id',
        'ota_status',
        'ota_error',
        'last_payload',
    ];

    /**
     * Pulses to send for a price — the ONE place this is calculated
     * (customer payments, technician app, admin re-send, refunds).
     * round(..., 6) stops float noise (2.0 / 0.01 = 200.00000000000003)
     * from turning 200 into 201.
     */
    public function pulsesFor(float $price): int
    {
        $pulsePrice = (float) $this->pulse_price;
        if ($pulsePrice <= 0) {
            return 0;
        }
        return (int) ceil(round($price / $pulsePrice, 6));
    }

    /** Max pulses one REMOTE_START may carry (see config/lbpay.php). */
    public static function maxPulsesPerStart(): int
    {
        return max(1, (int) config('lbpay.max_pulses_per_start', 1000));
    }

    /**
     * Returns a human-readable problem if any configured price would need
     * more pulses than the controller accepts, else null. Used when saving
     * device parameters, so a misconfiguration is caught by the admin
     * instead of by a paying customer.
     */
    public static function pulseLimitProblem(array $prices, float $pulsePrice): ?string
    {
        if ($pulsePrice <= 0) {
            return null;
        }
        $max = self::maxPulsesPerStart();
        foreach ($prices as $label => $price) {
            if ((float) $price <= 0) {
                continue;
            }
            $pulses = (int) ceil(round((float) $price / $pulsePrice, 6));
            if ($pulses > $max) {
                $maxPrice = number_format($max * $pulsePrice, 2);
                return "{$label} RM" . number_format((float) $price, 2) . " needs {$pulses} pulses at RM"
                    . rtrim(rtrim(number_format($pulsePrice, 4), '0'), '.') . "/pulse, but the controller accepts at most {$max} "
                    . "pulses per start (max RM{$maxPrice}). Raise the pulse price or LBPAY_MAX_PULSES_PER_START.";
            }
        }
        return null;
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }
    // Each device can be linked to one outlets via DeviceOutlet
    public function deviceOutlets()
    {
        //return $this->hasMany(DeviceOutlet::class, 'device_serial_number', 'serial_number');
        return $this->hasOne(DeviceOutlet::class, 'device_serial_number', 'serial_number');
    }
    public function auditLogs() {
        return $this->hasMany(DeviceAuditLog::class);
    }
    public function movementLogs()
    {
        // Use the foreign key column name in your table if it's not 'device_id'
        return $this->hasMany(DeviceMovementLog::class, 'device_serial_number', 'serial_number');
    }
}

