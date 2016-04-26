<?php

namespace App\Services;

abstract class AbstractBotCommands {

  public $request;
  public $user;    /* @var $this->user \App\Models\User */
  public $bot;     /* @var $this->bot \App\Models\Bot */
  public $sender;  /* @var $this->sender \App\Services\Bot */

  public $chat_id;

  function __construct($request, $user, $sender_service) {
    $this->request = $request;
    $this->user    = $user;
    $this->sender  = $sender_service;
    $this->chat_id = $this->request->message->chat->id;
  }

  protected function checkForAdmin() {
    return (boolean) $this->user->admin;
  }

  protected function checkForSuperAdmin() {
    return (boolean) $this->user->superadmin;
  }

}