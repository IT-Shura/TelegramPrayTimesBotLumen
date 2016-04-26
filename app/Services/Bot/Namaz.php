<?php

namespace App\Services\Bot;

use App\Jobs\TelegramDelivery;
use App\Models\Delivery as DeliveryModel;
use App\Models\DeliveryType;
use App\Models\User;
use App\Services\AbstractBotCommands;

/**
 * Рассылка сообщений
 */
class Namaz extends AbstractBotCommands {

    private function delivery_commands($delivery) {
        $commands = [
            'delivery_info'                     => 'показать информацию о текущей рассылке',
            /*'delivery_type'                     => 'изменить тип рассылки',*/
        ];

        if ($delivery->text OR $delivery->file) {
            $commands['delivery_start'] = 'запустить рассылку';
        }

        if ($delivery->text) {
            $commands['delivery_delete_text'] = 'очистить текст рассылки';
        }

        if ($delivery->file) {
            $commands['delivery_remove_file']        = 'открепить подключенный к рассылке файл';
            $commands['delivery_test_send_file'] = 'проверить отправку файла рассылки';
        }

        $commands['cancel'] = 'отменить создание рассылки';

        return $commands;
    }

    function commandLocation() {
        $this->user->state = 'location';
        $this->user->save();

        return [
            'text' => "Пожалуйста, укажите локацию, на которой вы находитесь в данный момент",
            'buttons' => [[[
                'text' => 'Указать своё местоположение',
                'request_location' => true
            ]]],
            'commands' => [
                'cancel' => 'Выйти из режима выбора локации',
            ]
        ];
    }

    function commandLocationMessages() {
        if (property_exists($this->request->message, 'location')) {
            $curl = new \Curl\Curl();
            $timezoneInfo = $curl->get('http://api.geonames.org/timezoneJSON', [
                'lat' => $this->request->message->location->latitude,
                'lng' => $this->request->message->location->longitude,
                'username' => 'believerufa',
            ]);
            
            $this->user->latitude  = $this->request->message->location->latitude;
            $this->user->longitude = $this->request->message->location->longitude;
            $this->user->timezone  = $timezoneInfo->gmtOffset;
            $this->user->state = null;
            $this->user->save();
            
            return [
                'text' => "Информация получена, благодарим.",
                'commands' => [
                    'namaz' => 'Узнать время намаза на сегодня.',
                ]
            ];
        } else {
            return [
                'text' => "Пожалуйста, укажите локацию или отмените текущую операцию.",
                'buttons' => [[[
                    'text' => 'Указать своё местоположение',
                    'request_location' => true
                ]]],
                'commands' => [
                    'cancel' => 'Выйти из режима выбора локации',
                ]
            ];
        }
    }
    
    function commandNamaz() {
        
        if ($this->user->latitude and $this->user->longitude and $this->user->timezone) {
            // Используем в качестве метода расчёта намаза "Islamic Society of North America (ISNA)"
            // как наиболее близкого и адекватного к реальности (по крайней мере, для Уфы)
            $prayTime = new \App\Helpers\PrayTime(2);
            $date  = strtotime(date('Y-m-d'));
            $times = $prayTime->getPrayerTimes($date, $this->user->latitude, $this->user->longitude, $this->user->timezone);
            
            $data = \IntlDateFormatter::formatObject(new \DateTime,'d MMMM Y', 'ru_RU.UTF8');
            
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
                'text' => "Прошу прощения, но вы ещё не указали своё местоположение. Без знания ваших координат (хотя-бы приблизительных), мы не можем определить время намаза для вас.",
                'buttons' => [[[
                    'text' => 'Указать своё местоположение',
                    'request_location' => true
                ]]],
            ];
        }
    }

}
