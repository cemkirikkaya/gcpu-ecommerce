<?php

namespace App\Console\Commands;

use App\Models\CartItem;
use Illuminate\Console\Command;

class ClearExpiredReservations extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'reservations:clear';

    /**
     * The console command description.
     */
    protected $description = 'Clear expired cart reservations';

    public function handle(): int
    {
        $count = CartItem::where('reserved_until', '<=', now())
            ->delete();

        $this->info("{$count} expired reservation(s) removed (after ".config('shop.reservation_minutes').' minutes).');

        return Command::SUCCESS;
    }
}
