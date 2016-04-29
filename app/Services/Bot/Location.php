<?php

namespace App\Services\Bot;

use App\Jobs\TelegramDelivery;
use App\Models\Delivery as DeliveryModel;
use App\Models\DeliveryType;
use App\Models\User;
use App\Services\AbstractBotCommands;
use IntlDateFormatter;
use DateTime;
use DateTimeZone;

/**
 * Определение локации пользователя
 */
class Location extends AbstractBotCommands {

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
            
            $times = $this->user->getPrayTimes();
            $data = IntlDateFormatter::formatObject(new DateTime('now', new DateTimeZone($this->user->getTimezoneName())),'cccccc, d MMMM Y', 'ru_RU.UTF8');
            $prayTimesText = "Время намаза на {$data}:\n"
              . "Фаджр: {$times[0]}\n"
              . "Восход: {$times[1]}\n"
              . "Зухр: {$times[2]}\n"
              . "Аср: {$times[3]}\n"
              . "Магриб: {$times[5]}\n"
              . "Иша: {$times[6]}"
            ;
            
            return [
                'text' => "Информация получена, благодарю. По умолчанию, наш бот будет оповещать вас о наступлении намаза. "
                        ."Вы всегда можете отключить данную функцию."
                        ."\n\nВы также можете выбрать различные методы расчёта времени наступления намаза.\n\n"
                        .$prayTimesText
                        ."\n\nДанное время является корректным? Не забывайте, в любом случае, это - лишь приблизительная информация. "
                        ."Если же время сильно расходится, попробуйте сменить метод расчёта. "
                        ."Также убедитесь, что вы указали точные координаты своёго местоположения. "
                        ."Если время всё равно некорректное, напишите об этом @BelieverUfa. "
                        ."Подумаем вместе о том, как решить эту проблему."
                ,
                'commands' => [
                    'namaz' => 'Узнать время намаза на сегодня.',
                    'select_method' => 'Выбрать метод расчёта времени намаза',
                ]
            ];
        } elseif (property_exists($this->request->message, 'text') and $this->request->message->text === 'Указать своё местоположение') {
            return [
                'text' => "Кажется, у вас установлена немного устаревшая версия Telegram. Пожалуйста, зайдите в Телеграм через браузер (web.telegram.org) или обновите программу на самую последнюю версию, чтобы получить возможность передать боту свои координаты.",
                'buttons' => [[[
                    'text' => 'Указать своё местоположение',
                    'request_location' => true
                ]]],
                'commands' => [
                    'cancel' => 'Выйти из режима выбора локации',
                ]
            ];
        } else {
            return [
                'text' => "Не вводите название города вручную! Нажмите внизу на кнопку «Указать своё местоположение» и немного подождите. Делайте это со своего мобильниого телефона или через браузер.",
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
    
}
