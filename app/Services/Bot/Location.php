<?php

namespace App\Services\Bot;

use App\Jobs\TelegramDelivery;
use App\Models\Delivery as DeliveryModel;
use App\Models\DeliveryType;
use App\Models\User;
use App\Services\AbstractBotCommands;

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
            
            return [
                'text' => "Информация получена, благодарим.",
                'commands' => [
                    'namaz' => 'Узнать время намаза на сегодня.',
                ]
            ];
        } else {
            return [
                'text' => "Пожалуйста, укажите ваше местоположение. Для этого нажмите внизу на кнопку «Указать своё местоположение». Без знания ваших координат (хотя-бы приблизительных), мы не можем определить время намаза для вас.",
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
