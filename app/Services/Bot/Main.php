<?php

namespace App\Services\Bot;

use App\Services\AbstractBotCommands;

/**
 * Основные команды
 */
class Main extends AbstractBotCommands {

    function commandStart() {
        $this->user->state = 'location';
        $this->user->save();
        return [
            'text' => "Ас-саляму алейкум уа рахматуллахи уа баракятух, {$this->user->name()}!\n\nДанный бот позволяет определять приблизительное время наступления намаза и оповещать вас об этом. "
                    . "Мы используем для расчёта алгоритмы из открытого проекта praytimes.org. По умолчанию используется метод расчёта от Исламского сообщества Северной Америки, "
                    . "как наиболее корректный, но вы всегда можете выбрать в настройках один из других алгоритмов, если они окажутся для вашего региона более подходящими."
                    . "\n\nДля начала работы с ботом, пожалуйста, укажите своё местоположение."
            ,
            'buttons' => [[[
                'text' => 'Указать своё местоположение',
                'request_location' => true
            ]]]
        ];
    }

    function commandHelp() {
        $answer['commands'] = [
            //'select_types' => 'Выбрать типы рассылок, на которые можно подписаться.',
            'namaz'         => 'Показать время начала намазов на сегодня.',
            'location'      => 'Указать своё местоположение',
            'select_method' => 'Выбрать метод расчёта времени намаза',
            'notifications' => 'Настройки оповещений о начале намаза',
        ];
        
        if ($this->checkForAdmin()) {
            $answer['commands']['new_delivery'] = 'Создать новую рассылку';
        }
        
        if ($this->checkForSuperAdmin()) {
            $answer['commands']['manage_admins'] = 'Управлять администраторами бота';
        }
        
        return $answer;
    }

    function commandCancel() {
        if ($this->user->state) {
            $this->user->state = null;
            $this->user->save();
            return [
                'text' => "Текущая операция отменена",
                'commands' => [
                    'help' => 'Помощь в работе с ботом',
                ]
            ];
        } else {
            return "У вас нет активных операций, поэтому и отменять вам нечего.";
        }
    }

}
