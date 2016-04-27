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
class Notifications extends AbstractBotCommands {

    function commandNotifications() {
        return [
            'text' => "Вы попали в раздел настроек оповещений о наступлении времени намаза. Выберите один из разделов настроек.",
            'commands' => [
                'notifications_on'  => 'Включить оповещения',
                'notifications_off' => 'Выключить оповещения',
                'help' => 'Основные команды бота',
            ]
        ];
    }

    function commandNotificationsOn() {
        $this->user->notifications = true;
        $this->user->save();
        return [
            'text' => "Оповещения о намазах активированы.",
        ];
    }

    function commandNotificationsOff() {
        $this->user->notifications = false;
        $this->user->save();
        return [
            'text' => "Оповещения о намазах отключены.",
        ];
    }

}
