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
            'text' => 'Пожалуйста, укажите локацию, на которой вы находитесь в данный момент. '
                     ."Можете нажать на кнопку внизу \"Указать своё местоположение\", чтобы отправить свои координаты.\n\n"
                     .'Имейте ввиду, что свои координаты вы можете отправить только с мобильного телефона. '
             ,
            'buttons' => [[[
                'text' => 'Отправить своё местоположение',
                'request_location' => true
            ]]],
            'commands' => [
                'cancel' => 'Выйти из режима выбора локации',
            ]
        ];
    }

    private function getPrayTimesText() {
        $times = $this->user->getPrayTimes();
        $data = IntlDateFormatter::formatObject(new DateTime('now', new DateTimeZone($this->user->getTimezoneName())),'cccccc, d MMMM Y', 'ru_RU.UTF8');
        return "Время намаза на {$data}:\n\n"
          . "Фаджр: {$times[0]}\n"
          . "Восход: {$times[1]}\n"
          . "Зухр: {$times[2]}\n"
          . "Аср: {$times[3]}\n"
          . "Магриб: {$times[5]}\n"
          . "Иша: {$times[6]}"
        ;
    }

    private function getTimezoneInfo($latitude, $longitude)  {
        $curl = new \Curl\Curl();
        return $curl->get('http://api.geonames.org/timezoneJSON', [
            'lat' => $latitude,
            'lng' => $longitude,
            'username' => 'believerufa',
        ]);
    }

    function commandLocationMessages() {
        if (property_exists($this->request->message, 'location')) {
            $latitude  = $this->request->message->location->latitude;
            $longitude = $this->request->message->location->longitude;

            $this->user->latitude  = $latitude;
            $this->user->longitude = $longitude;
            $this->user->timezone  = $this->getTimezoneInfo($latitude, $longitude)->gmtOffset;
            $this->user->state     = null;
            $this->user->save();

            $locationSavedText =
                "Информация получена, благодарю. По умолчанию, наш бот будет оповещать вас о наступлении намаза. "
                ."Вы всегда можете отключить данную функцию."
                ."\n\nВы также можете выбрать различные методы расчёта времени наступления намаза.\n\n"
                .$this->getPrayTimesText()
                ."\n\nДанное время является корректным? Не забывайте, в любом случае, это - лишь приблизительная информация. "
                ."Если же время сильно расходится, попробуйте сменить метод расчёта."
            ;

            return [
                'text' => $locationSavedText,
                'commands' => [
                    'namaz' => 'Узнать время намаза на сегодня.',
                    'select_method' => 'Выбрать метод расчёта времени намаза',
                ]
            ];
        } else {
            return [
                'text' => 'Пожалуйста, отправьте своё текущее расположение, иначе бот не сможет раcсчитывать для вас времена наступления намазов.',
                'buttons' => [[[
                    'text' => 'Отправить своё местоположение',
                    'request_location' => true
                ]]],
            ];
        }
    }

}
