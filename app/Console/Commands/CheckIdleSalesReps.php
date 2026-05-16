<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
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
    protected $description = 'Check all active sales reps idle status';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        app(IdleDetectionService::class)->checkAllSalesReps();

        $this->info('Idle check completed successfully.');

        return self::SUCCESS;
    }
}
