<?php

namespace App\Services\Bot;

use App\Jobs\TelegramDelivery;
use App\Models\Delivery as DeliveryModel;
use App\Models\DeliveryType;
use App\Models\User;
use App\Services\AbstractBotCommands;

use DateTime;
use DateTimeZone;
use IntlDateFormatter;

/**
 * Получение времени намаза
 */
class Namaz extends AbstractBotCommands {

    function commandNamaz() {
        
        if ($this->user->latitude and $this->user->longitude and $this->user->timezone) {
            $times = $this->user->getPrayTimes();
            $data = IntlDateFormatter::formatObject(new DateTime('now', new DateTimeZone($this->user->getTimezoneName())),'cccccc, d MMMM Y', 'ru_RU.UTF8');
            
            $text = "Время намаза на {$data}:\n"
              . "Фаджр: {$times[0]}\n"
              . "Восход: {$times[1]}\n"
              . "Зухр: {$times[2]}\n"
              . "Аср: {$times[3]}\n"
              //. "Закат: {$times[4]}\n"
              . "Магриб: {$times[5]}\n"
              . "Иша: {$times[6]}"
            ;

            return [
              'text' => $text,
              'commands' => [
                'help' => 'Прочие команды бота',
              ]
            ];
            
        } else {
            $this->user->state = 'location';
            $this->user->save();
            return [
                'text' => "Пожалуйста, укажите ваше местоположение. Для этого нажмите внизу на кнопку «Указать своё местоположение». Без знания ваших координат (хотя-бы приблизительных), мы не можем определить время намаза для вас.",
                'buttons' => [[[
                    'text' => 'Указать своё местоположение',
                    'request_location' => true
                ]]],
            ];
        }
    }
    
    function commandSelectMethod() {
        $this->user->state = 'select_method';
        $this->user->save();
        
        $date = strtotime(date('Y-m-d'));
        $data = \IntlDateFormatter::formatObject(new \DateTime,'d MMMM Y', 'ru_RU.UTF8');
        
        $text = "Выберите наиболее подходящие значения для своего региона. Для каждого метода отображается время на фаджр, восход, зухр, аср, магриб и ишу.\n\n";
        
        foreach(range(0,9) as $methodId) {
            $prayTime = new \App\Helpers\PrayTime($methodId);
            $times = $prayTime->getPrayerTimes($date, $this->user->latitude, $this->user->longitude, $this->user->timezone);
            $methodNum = str_pad($methodId+1, 3, ' ', STR_PAD_RIGHT);
            $text .= "№{$methodNum}: ";
            $text .= "{$times[0]} - {$times[1]} - {$times[2]} - {$times[3]} - {$times[5]} - {$times[6]}\n\n";
        }

        $currentMethod = $this->user->method + 1;
        $text .= "\nВведите номер наиболее близкого для вас метода расчёта, чтобы сделать его временем, которое будет отображаться для вас.";
        $text .= "\n\nВ данный момент вы используете метод №{$currentMethod}.";
        
        return [
            'text' => $text,
            'commands' => [
                'cancel' => 'Выйти из режима выбора метода расчёта времени намаза.',
            ],
            'buttons' => [
                [ '1', '2', '3', '4', '5' ],
                [ '6', '7', '8', '9', '10' ],
            ]
        ];
    }
    
    function commandSelectMethodMessages() {
        if (property_exists($this->request->message, 'text')) {
            
            $methodId = ((int) $this->request->message->text) - 1;
            
            if ($methodId >= 0 and $methodId <= 9) {
                $this->user->method = ((int) $this->request->message->text) - 1;
                $this->user->state = null;
                $this->user->save();
                
                return [
                    'text' => "Вы указали метод расчёта №" . ($methodId+1) . '.',
                    'commands' => [
                        'namaz' => 'Узнать время намаза на сегодня',
                    ]
                ];
            } else {
                return [
                    'text' => "Укажите номер метода расчёта или отмените данную команду.",
                    'commands' => [
                        'cancel' => 'Отменить команду',
                    ]
                ];
            }
        }        
    }

}
