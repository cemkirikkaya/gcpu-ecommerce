<?php

namespace App\Console\Commands;

use App\Services\OrderShipmentService;
use Illuminate\Console\Command;

class SyncGeliverShipments extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'geliver:sync-shipments';

    /**
     * The console command description.
     */
    protected $description = 'Sync in-transit Geliver shipments from the API';

    public function handle(OrderShipmentService $orderShipmentService): int
    {
        if (! config('geliver.auto_sync_from_api')) {
            $this->info('Geliver API otomatik senkronizasyonu devre dışı.');

            return self::SUCCESS;
        }

        if (config('geliver.fake')) {
            $this->info('Sahte Geliver modunda API senkronu atlandı.');

            return self::SUCCESS;
        }

        $syncedCount = $orderShipmentService->syncPendingShipments();

        $this->info("{$syncedCount} Geliver gönderisi senkronize edildi.");

        return self::SUCCESS;
    }
}
