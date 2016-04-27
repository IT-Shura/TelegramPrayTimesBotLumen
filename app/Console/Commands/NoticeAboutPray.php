<?php

namespace App\Console\Commands;

use App\User;
use App\DripEmailer;
use Illuminate\Console\Command;
use DateTime;
use DateTimeZone;

class NoticeAboutPray extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notice-about-pray';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Оповестить людей о наступлении намаза';
    
    protected $bot;
    
    /**
     * Create a new command instance.
     *
     * @param  DripEmailer  $drip
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->bot = app('Bot');
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $noticeUsers = \App\Models\User::query()
            ->where('notifications', true)
            ->whereNotNull('timezone')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get();
            
        $namazNames = [
            0 => 'Фаджр',
            2 => 'Зухр',
            3 => 'Аср',
            5 => 'Магриб',
            6 => 'Иша',
        ];
            
        foreach($noticeUsers as $user) {
            
            $timezone = $user->timezone * -1;
            if ($timezone >= 0) { $timezone = '+' . ((string) $timezone); }
            $timezone = (string) $timezone;
            
            $now       = (new DateTime('now', new DateTimeZone("Etc/GMT{$timezone}")));
            $prayTimes = $user->getPrayTimes($now);
            $this->info($now->format('G:i') . " -- {$timezone} ({$user->timezone})");
            
            foreach([0,2,3,5,6] as $prayTimeId) {
                foreach([15, 5] as $intervalInMins) {
                    $prayTimeDate = new DateTime($prayTimes[$prayTimeId], new DateTimeZone("Etc/GMT{$timezone}"));
                    
                    $interval = $now->getTimestamp() - $prayTimeDate->getTimestamp();
                    $this->info($user->name() . ' -- ' . $prayTimeDate->format('G:i') . ' -- ' . $interval);
                    
                    // За $intervalInMins минут оповестим человека о подходящем намазе..
                    if ($interval >= (60 * ($intervalInMins+2) * -1) and $interval <= (60 * ($intervalInMins-3) * -1)) {
                        
                        $notification = \App\Models\NamazNotification::firstOrNew([
                            'users_id'   => $user->id,
                            'date'       => $now->format('G:i'),
                            'namaz_type' => $prayTimeId,
                            'minutes'    => $intervalInMins
                        ]);
                        
                        if ( ! $notification->exists) {
                            $notification->save();
                            $this->bot->sendAnswer($this->bot->createReply("Сообщаем вам, что {$namazNames[$prayTimeId]} намаз наступит уже через {$intervalInMins} минут."), $user->id);
                        }
                    }
                }
            }
            
        }
    }
}