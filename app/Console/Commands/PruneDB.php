<?php

namespace App\Console\Commands;

use App\User;
use App\DripEmailer;
use App\Models\MessageHistory;
use App\Models\NamazNotification;
use Illuminate\Console\Command;
use DateTime;
use DateTimeZone;
use DB;

class PruneDB extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'prune-db';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Очистка истории сообщений из БД';
    
    /**
     * Create a new command instance.
     *
     * @param  DripEmailer  $drip
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        MessageHistory::query()
            ->where('created_at', '<', DB::raw("now() - interval '80 days'"))
            ->delete()
        ;
        
        NamazNotification::query()
            ->where('created_at', '<', DB::raw("now() - interval '80 days'"))
            ->delete()
        ;
    }
}