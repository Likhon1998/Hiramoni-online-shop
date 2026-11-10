<?php

namespace App\Console\Commands;

use App\Services\CourierStatusSyncService;
use Illuminate\Console\Command;

class SyncCourierStatuses extends Command
{
    protected $signature = 'courier:sync-status {--shop= : Limit to one shop id} {--limit=80}';

    protected $description = 'Pull delivery status from connected courier APIs and update orders';

    public function handle(CourierStatusSyncService $sync): int
    {
        $shopId = $this->option('shop') ? (int) $this->option('shop') : null;
        $updated = $sync->syncOpen($shopId, (int) $this->option('limit'));
        $this->info("Updated {$updated} consignments from courier APIs.");

        return self::SUCCESS;
    }
}
