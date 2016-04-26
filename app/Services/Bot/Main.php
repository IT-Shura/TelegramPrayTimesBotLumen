<?php

namespace App\Services\Bot;

use App\Services\AbstractBotCommands;

/**
 * Основные команды
 */
class Main extends AbstractBotCommands {

    function commandStart() {
        return [
            'text' => "Ас-саляму алейкум уа рахматуллахи уа баракятух, {$this->user->name()}!\n\nДанный бот позволяет определять времена намаза и оповещать вас о них.",
            'commands' => [
                'help' => 'Помощь по работе с ботом',
                'location' => 'Указать своё местоположение',
            ],
        ];
    }

    function commandHelp() {
        $answer['commands'] = [
            //'select_types' => 'Выбрать типы рассылок, на которые можно подписаться.',
            'namaz' => 'Показать время начала намазов на сегодня.',
            'location' => 'Указать своё местоположение',
        ];
        
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
