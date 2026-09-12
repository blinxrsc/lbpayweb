<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
//add on 29-12-25
use Spatie\Permission\Traits\HasRoles;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class User extends Authenticatable
{
    use LogsActivity;

    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;
    /**add permission for users - afia 29-12-25 */
    use HasRoles;
    // Lets User::createToken() issue Sanctum API tokens for the technician mobile app
    use HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email', 'roles']) // Log changes to these fields
            ->logOnlyDirty() // Only log if something actually changed
            ->dontSubmitEmptyLogs();
    }

    /**
     * Outlets this user has explicitly been granted access to.
     * Ignored for users who canAccessAllOutlets() (e.g. admins).
     */
    public function outlets()
    {
        return $this->belongsToMany(Outlet::class, 'outlet_user');
    }

    /**
     * Users with this permission see/manage every outlet, regardless of
     * what's in the outlet_user pivot table. Give this to the 'admin' role.
     */
    public function canAccessAllOutlets(): bool
    {
        return $this->can('outlets.view-all');
    }

    /**
     * List of outlet IDs this user is allowed to access.
     * Returns null to mean "unrestricted / all outlets".
     */
    public function accessibleOutletIds(): ?array
    {
        if ($this->canAccessAllOutlets()) {
            return null;
        }

        return $this->outlets()->pluck('outlets.id')->all();
    }

    /**
     * Whether this user may access the given outlet.
     */
    public function canAccessOutlet(int|string $outletId): bool
    {
        if ($this->canAccessAllOutlets()) {
            return true;
        }

        return $this->outlets()->where('outlets.id', $outletId)->exists();
    }
}
