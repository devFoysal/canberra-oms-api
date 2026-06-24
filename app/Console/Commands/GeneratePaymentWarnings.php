<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\PaymentWarningService;

class GeneratePaymentWarnings extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'payment:warnings';

    /**
     * The console command description.
     */
    protected $description = 'Generate payment warnings safely and efficiently';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // 1. Force fresh DB state for daemonized schedulers (Fixes "MySQL server has gone away")
        DB::purge();
        DB::reconnect();

        Log::info('payment:warnings execution started.');
        $this->info('Generating payment warnings...');

        // 2. Wrap in a try-catch block to guarantee application and scheduler safety
        try {

            // Execute your business logic service
            app(PaymentWarningService::class)->generateWarnings();

            $msg = 'Payment warnings generated successfully.';
            $this->info($msg);
            Log::info($msg);

            return 0; // Success (Tells Ubuntu scheduler everything is green)

        } catch (\Throwable $e) {

            // 3. CRITICAL FAILURE CATCH: Capture why the task crashed and log it cleanly
            $errorMsg = "payment:warnings failed: {$e->getMessage()} in {$e->getFile()}:{$e->getLine()}";
            $this->error($errorMsg);
            Log::error($errorMsg);

            return 1; // Failure (Alerts the system that the cron job failed)
        }
    }
}
