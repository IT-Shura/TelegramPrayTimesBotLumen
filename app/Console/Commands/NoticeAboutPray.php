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
    
    private $phrases = [
        0 => [ // Фразы на фаджр
            15 => [
                'Наступает ещё один день. Начни же его с наилучшего дела, мой дорогой раб Аллаха. Ты знаешь, о чём я :)',
                'Просыпайся, мой друг. Вспомни о том, сколькими благами одарил тебя Аллах, и приди же к Нему с чистым сердцем. Подготовься к намазу..',
                'Доброе утро :) Совсем скоро солнце появится из-за горизонта. Подготовься на предрассветный намаз, мой друг.',
                'Ас-саляму алейкум! Рассвет вот-вот наступит, мой друг. Пусть твой день будет наполнен полезными делами, и, конечно, не забудь пообщаться со своим Господом в эти прекрасные утренние минуты.',
            ],
            'friday_15' => [
                'Ас-саляму алейкум, мой друг! Сегодня пятница, время совместной пятничной молитвы. Постарайся принять полное омовение, подготовься к посещению мечети. Если есть время - постарайся прочесть суру "аль-Кахф", ведь это сунна. Через несколько минут наступит время утреннего намаза :)',
            ],
            0 => [
                'Рассвет наступил',
                'Время намаза наступило',
                'Твой Господь ожидает вашей беседы :)',
            ],
        ],
        2 => [ // Зухр
            15 => [
                'Ас-саляму алейкум, мой друг :) Отложи на несколько минут дела свои.. Наступает время полуденной молитвы.',
                'Время полуденного намаза совсем близко, приостанови свои дела и пообщайся со своим Господом)',
            ],
            0 => [
                'Время наступило.',
                'Время полуденного намаза наступило.',
                'Аллах уже ждёт, когда ты заговоришь с Ним. Иди же к Нему, не пропускай начало молитвы :)',
            ]
        ],
        3 => [ // Аср
            15 => [
                'Ас-саляму алейкум) Время подходит к послеполуденному намазу. Сделай же перерыв в делах своих, и пообщайся со своим Господом :)',
                'Подходит время для твоего общения с самой авторитетной личностью всех миров. Аллах ждёт тебя на послеполуденную молитву, мой друг)',
            ],
            0 => [
                'Время намаза подошло.',
                'Время намаза наступило.',
                'Время послеполуденного намаза пришло.',
            ]
        ],
        5 => [ // Магриб
            15 => [
                'Ас-саляму алейкум, мой дорогой друг :) Наступает время вечерней молитвы.',
                'Дорогой друг, я хочу напомнить тебе, что скоро наступит время вечернего намаза)',
                'Отложи свои дела на несколько минут общения со своим Господом :) Вечерний намаз вот-вот наступит.',
            ],
            0 => [
                'Время намаза настало.',
                'Не задерживай свою вечернюю молитву, ведь солнце быстро садится) Время подошло.',
                'Как же прекрасен тот человек, который понял эту жизнь и встал на прямой путь, и усердно совершает земные поклоны, благодаря своего Господа..',
            ],
        ],
        6 => [ // Иша
            15 => [
                'Порадуй своего Господа искренней ночной молитвой, которая уже вот-вот наступит :)',
                'Несколько минут, и начнётся время ночной молитвы, мой друг)',
                'Наступает время ночной молитвы, мой друг :)',
            ],
            0 => [
                'Что может быть лучше из всего, чем можно заниматься в мире этом, чем общение с Творцом небес и земли?',
                'Не забывай своего Господа, ведь Он столько всего делает для тебя.',
                'Пусть Аллах сделает благой твою жизнь и жизнь твоих родственников, и сделает благим твоё общение с Ним. Время ночного намаза наступило.',
            ]
        ],
    ];

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
            
        foreach($noticeUsers as $user) {
            
            $timezone  = $user->getTimezoneName();
            $now       = new DateTime('now', new DateTimeZone($timezone));
            $prayTimes = $user->getPrayTimes($now);
            
            $this->info($now->format('G:i') . " -- {$timezone} ({$user->timezone})");
            
            //app('log')->info("Trying to send notify to {$user->id}. {$user->name}");
            
            foreach([0,2,3,5,6] as $prayTimeId) {
                foreach([15, 0] as $intervalInMins) {
                    
                    // Если программе не удалось получить корректное время на данный промежуток - ничего не будем делать
                    if ($prayTimes[$prayTimeId] === '-----') { continue; }
                    
                    $prayTimeDate = new DateTime($prayTimes[$prayTimeId], new DateTimeZone($timezone));
                    
                    $interval = $now->getTimestamp() - $prayTimeDate->getTimestamp();
                    
                    $this->info($user->name() . ' -- ' . $prayTimeDate->format('G:i') . ' -- ' . $interval);
                    
                    // За $intervalInMins минут оповестим человека о подходящем намазе..
                    if ($interval >= (60 * ($intervalInMins+1) * -1) and $interval <= (60 * ($intervalInMins-1) * -1)) {
                        
                        $notification = \App\Models\NamazNotification::firstOrNew([
                            'users_id'   => $user->id,
                            'date'       => $prayTimeDate->getTimestamp(),
                            'namaz_type' => $prayTimeId,
                            'minutes'    => $intervalInMins
                        ]);
                        
                        if ( ! $notification->exists) {
                            $notification->save();
                            
                            if ($now->format('w') === '5' and $prayTimeId === 0 and $intervalInMins === 15) {
                                $phrasesForThisEvent = $this->phrases[$prayTimeId]["friday_$intervalInMins"];
                            } else {
                                $phrasesForThisEvent = $this->phrases[$prayTimeId][$intervalInMins];
                            }
                            
                            $phraseForThisEvent = $phrasesForThisEvent[array_rand($phrasesForThisEvent)];
                            
                            $this->bot->sendAnswer($this->bot->createReply($phraseForThisEvent), $user->id);
                        }
                    }
                }
            }
            
        }
    }
}
