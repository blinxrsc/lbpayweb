<?php

namespace App\Observers;

use App\Models\Outlet;
use Illuminate\Support\Facades\Cache;

class OutletObserver
{
    // Clear cache whenever data changes
    private function clearCache() {
        //Cache::forget('outlet_dashboard_stats');
        // This clears ALL cached items. It's the safest way to ensure
        // all different date-range reports stay accurate.
        Cache::flush();
    }
    /**
     * Handle the Outlet "created" event.
     */
    public function created(Outlet $outlet): void
    {
        $this->clearCache();
    }

    /**
     * Handle the Outlet "updated" event.
     */
    public function updated(Outlet $outlet): void
    {
        $this->clearCache();
    }

    /**
     * Handle the Outlet "deleted" event.
     */
    public function deleted(Outlet $outlet): void
    {
        $this->clearCache();
    }

    /**
     * Handle the Outlet "restored" event.
     */
    public function restored(Outlet $outlet): void
    {
        //
    }

    /**
     * Handle the Outlet "force deleted" event.
     */
    public function forceDeleted(Outlet $outlet): void
    {
        //
    }
}
