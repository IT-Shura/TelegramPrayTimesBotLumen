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
                     ."Можете нажать на кнопку внизу \"Указать своё местоположение\", чтобы автоматически отправить свои координаты.\n\n"
                     .'Также вы можете написать название своего населённого пункта словами, например: "Уфа". '
                     .'Бот постарается найти координаты данной местности. Но, следует иметь ввиду, что бот может '
                     .'найти не тот населённый пункт, тогда уточните запрос, например так: "Россия, Башкортостан, Уфа"'
             ,
            'buttons' => [[[
                'text' => 'Указать своё местоположение',
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
        return "Время намаза на {$data}:\n"
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
            
            return [
                'text' => "Информация получена, благодарю. По умолчанию, наш бот будет оповещать вас о наступлении намаза. "
                        ."Вы всегда можете отключить данную функцию."
                        ."\n\nВы также можете выбрать различные методы расчёта времени наступления намаза.\n\n"
                        .$this->getPrayTimesText()
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
            // Если было послано какое-то обычное сообщение, попытаемся найти координаты через API Яндекса
            $curl = new \Curl\Curl();
            $location = $curl->get('https://geocode-maps.yandex.ru/1.x/', [
                'geocode' => $this->request->message->text,
            ]);
            
            if (property_exists($location->GeoObjectCollection, 'featureMember')) {
                
                $coords = explode(' ', (string) $location->GeoObjectCollection->featureMember[0]->GeoObject->Point->pos);
                
                $this->user->latitude  = $coords[1];
                $this->user->longitude = $coords[0];
                $this->user->timezone  = $this->getTimezoneInfo($coords[1],$coords[0])->gmtOffset;
                $this->user->state     = null;
                $this->user->save();
                
                $this->sender->sendAnswer([
                    'method'  => 'sendLocation',
                    'message' => [
                        'latitude'  => $coords[1],
                        'longitude' => $coords[0],
                    ],
                ]);                
                
                return [
                    'text' => "Информация получена, благодарю. По умолчанию, наш бот будет оповещать вас о наступлении намаза. "
                            ."Вы всегда можете отключить данную функцию."
                            ."\n\nВы также можете выбрать различные методы расчёта времени наступления намаза.\n\n"
                            .$this->getPrayTimesText()
                            ."\n\nДанное время является корректным? Не забывайте, в любом случае, это - лишь приблизительная информация. "
                            ."Если же время сильно расходится, попробуйте сменить метод расчёта. "
                            ."Также убедитесь, что вы указали точные координаты своёго местоположения. "
                            ."Если время всё равно некорректное, напишите об этом @BelieverUfa. "
                            ."Подумаем вместе о том, как решить эту проблему."
                    ,
                    'commands' => [
                        'namaz'         => 'Узнать время намаза на сегодня.',
                        'select_method' => 'Выбрать метод расчёта времени намаза',
                        'location'      => 'Указать другие координаты',
                    ]
                ];
            } else {
                return [
                    'text' => "Прошу прощения, но я не нашёл ничего подходящего по вашему запросу. "
                            ."Попробуйте передать своё местоположение ещё раз "
                    ,
                    'buttons' => [[[
                        'text' => 'Указать своё местоположение',
                        'request_location' => true
                    ]]],
                    'commands' => [
                        'cancel' => 'Выйти из режима выбора локации',
                    ],
                ];
            }
        }
    }
    
}
