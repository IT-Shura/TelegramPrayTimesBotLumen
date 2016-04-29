<?php

namespace App\Jobs;

use App\Models\Bot;
use App\Models\Delivery;
use App\Models\User;
use App\Services\Bots\ShuraRB;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Bus\SelfHandling;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Symfony\Component\Security\Core\User\User as User2;

class TelegramDelivery implements SelfHandling, ShouldQueue {
  /*
  |--------------------------------------------------------------------------
  | Queueable Jobs
  |--------------------------------------------------------------------------
  |
  | This job base class provides a central location to place any logic that
  | is shared across all of your jobs. The trait included with the class
  | provides access to the "queueOn" and "delay" queue helper methods.
  |
 */

  use InteractsWithQueue, Queueable, SerializesModels;

  protected $delivery;
  protected $service;

  /**
   * Create a new job instance.
   *
   * @param  User2  $user
   * @return void
   */
  public function __construct(Delivery $delivery)
  {
      $this->delivery = $delivery;
      $this->service  = app('Bot'); /* @var $this->service \App\Services\Bot */
  }

  private function messageForAdmins($message) {
    foreach(User::where('admin',true)->get() as $user) {
        $this->service->sendAnswer($this->service->createReply($message), $user->id);
    }
  }

  /**
   * Execute the job.
   *
   * @return void
   */
  public function handle()
  {
    $this->delivery->status_id = DELIVERY_STATUS_RECEIVING;
    $this->delivery->save();

    User::chunk(200, function ($users) {
        foreach($users as $user) {
          if ($this->delivery->text) {
            $message = $this->service->createReply([
              'text' => $this->delivery->text,
            ]);
            $this->service->sendAnswer($message, $user->id);
          }
    
          if ($this->delivery->file) {
            $message = $this->service->createReply([
              'sendDocument' => $this->delivery,
            ]);
            $this->service->sendAnswer($message, $user->id);
          }
        }        
    });
    
    $this->delivery->status_id = DELIVERY_STATUS_SUCCESS_END;
    $this->delivery->save();
    
    $this->messageForAdmins([
      'text' => "Рассылка пользователя {$this->delivery->user->name()} №{$this->delivery->id} завершила свою работу и была доставлена всем подписчикам."
    ]);
    
  }
}