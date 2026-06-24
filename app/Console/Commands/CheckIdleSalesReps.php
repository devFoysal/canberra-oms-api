<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\IdleDetectionService;

class CheckIdleSalesReps extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check-idle';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check all active sales reps idle status safely and efficiently';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // 1. Force fresh DB state for daemonized schedulers (Crucial for high-frequency tasks)
        DB::purge();
        DB::reconnect();

        Log::info('app:check-idle execution started.');
        $this->info('Checking sales reps idle status...');

        // 2. Wrap in a try-catch block to guarantee application and scheduler safety
        try {

            // Execute your business logic service
            app(IdleDetectionService::class)->checkAllSalesReps();

            $msg = 'Idle check completed successfully.';
            $this->info($msg);
            Log::info($msg);

            return 0; // Success (Tells Ubuntu scheduler everything is clean)

        } catch (\Throwable $e) {

            // 3. CRITICAL FAILURE CATCH: Capture why the task crashed and log it cleanly
            $errorMsg = "app:check-idle failed: {$e->getMessage()} in {$e->getFile()}:{$e->getLine()}";
            $this->error($errorMsg);
            Log::error($errorMsg);

            return 1; // Failure (Alerts the system that the cron job failed)
        }
    }
}
