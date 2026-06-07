<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\LocationPoint;
use Illuminate\Support\Facades\DB;

class CleanLocationPoints extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'location:clean';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean all LocationPoint records daily';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        LocationPoint::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->info('LocationPoint table truncated successfully');

        return Command::SUCCESS;
    }
}
