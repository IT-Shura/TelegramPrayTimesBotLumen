<?php

namespace App\Http\Controllers;

use App\Models\Bot;
use App\Models\DeliveryType;
use App\Models\User;

class Telegram extends Controller {

  private $request;
  private $bot;

  /**
   * Функция используется для отладки, позволяет скинуть текущий объект запроса в лог-файл
   */
  function dumpRequest() {
      bot_debug($this->request);
  }

  function Webhook($key) {
    
    if ($key !== env('TELEGRAM_KEY')) {
      return '?';
    }
    
    $data = file_get_contents('php://input');
    if ($data) {
      $this->request = json_decode($data);
    }
    
    //$this->dumpRequest();
    
    // любые не-сообщения от людей просто игнорируем
    if (! property_exists($this->request,'message')) {
      return 'thanks';
    }

    $this->user = User::find($this->request->message->from->id);

    $userName   = property_exists($this->request->message->from, 'first_name') ? $this->request->message->from->first_name : null;
    $userFamily = property_exists($this->request->message->from, 'last_name')  ? $this->request->message->from->last_name  : null;
    $userNick   = property_exists($this->request->message->from, 'username')   ? $this->request->message->from->username   : null;

    // Такого пользователя мы еще не встречали. Добавим его в нашу базу.
    if (is_null($this->user))
    {
      $this->user = new User([
        'id'     => $this->request->message->from->id,
        'name'   => $userName,
        'family' => $userFamily,
        'nick'   => $userNick,
      ]);
    
      $this->user->save();
      $this->user = User::find($this->request->message->from->id);
      $this->user
        ->deliveries_types()
        ->sync(DeliveryType::where('enabled_by_default',true)
            ->get()
            ->lists('id')
            ->toArray()
        );
    }

    // Если у пользователя обновились имя, фамилия или никнейм - обновим их и у себя в БД
    if ($this->user->name !== $userName or $this->user->family !== $userFamily or $this->user->nick !== $userNick)
    {
      $this->user->name   = $userName;
      $this->user->family = $userFamily;
      $this->user->nick   = $userNick;
      $this->user->save();
    }

    $bot_service = app('Bot');
    $bot_service->processCommand($this->request, $this->user);
    return 'thanks!';
  }

}
