<?php

namespace App\Console\Commands;

use App\Models\Booking;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('bookings:cleanup-expired')]
#[Description('Delete bookings whose 10-minute hold has expired')]
class CleanupExpiredBookings extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $deletedCount = Booking::cleanupExpiredHolds();

        $this->info("Deleted {$deletedCount} expired held booking(s).");

        return self::SUCCESS;
    }
}
