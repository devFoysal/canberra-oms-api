<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LocationClean extends Command
{
    protected $signature = 'location:clean';
    protected $description = 'Clean all LocationPoint records safely and efficiently';

    public function handle()
    {
        // 1. Force fresh DB state for daemonized schedulers
        DB::purge();
        DB::reconnect();

        Log::info('location:clean execution started.');

        // 2. Wrap in a try-catch-finally block to guarantee database safety
        try {
            DB::transaction(function () {
                // Disable foreign key checks within the isolated session
                DB::statement('SET FOREIGN_KEY_CHECKS=0;');

                $sessionsDeleted = DB::table('location_sessions')->delete();
                $pointsDeleted = DB::table('location_points')->delete();

                // Re-enable foreign key checks immediately
                DB::statement('SET FOREIGN_KEY_CHECKS=1;');

                $summary = "Cleaned: {$sessionsDeleted} sessions, {$pointsDeleted} points.";
                $this->info($summary);
                Log::info($summary);
            });

            return 0; // Success

        } catch (\Throwable $e) {
            // 3. CRITICAL FAILURE CATCH: Ensure the DB isn't left exposed if a crash occurs mid-deletion
            try {
                DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            } catch (\Exception $fkEx) {
                // DB connection completely dead, ignore
            }

            $errorMsg = "location:clean failed: {$e->getMessage()} in {$e->getFile()}:{$e->getLine()}";
            $this->error($errorMsg);
            Log::error($errorMsg);

            return 1; // Failure (tells Ubuntu scheduler something went wrong)
        }
    }
}
