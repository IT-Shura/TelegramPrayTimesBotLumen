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

    // Такого пользователя мы еще не встречали. Добавим его в нашу базу.
    if (is_null($this->user))
    {
      $this->user = new User([
        'id'     => $this->request->message->from->id,
        'name'   => property_exists($this->request->message->from, 'first_name') ? $this->request->message->from->first_name : null,
        'family' => property_exists($this->request->message->from, 'last_name')  ? $this->request->message->from->last_name  : null,
        'nick'   => property_exists($this->request->message->from, 'username')   ? $this->request->message->from->username   : null,
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

    $bot_service = app('Bot');
    $bot_service->processCommand($this->request, $this->user);
    return 'thanks!';
  }

}
