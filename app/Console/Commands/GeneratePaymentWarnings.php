<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
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
    protected $description = 'Generate payment warnings';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->info('Generating payment warnings...');

        app(PaymentWarningService::class)
            ->generateWarnings();

        $this->info('Payment warnings generated successfully.');
    }
}
