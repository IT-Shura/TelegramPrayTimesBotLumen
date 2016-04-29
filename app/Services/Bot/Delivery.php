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
class Delivery extends AbstractBotCommands {

  private function delivery_commands($delivery) {
    $commands = [
      'delivery_info'           => 'показать информацию о текущей рассылке',
      /*'delivery_type'           => 'изменить тип рассылки',*/
    ];

    if ($delivery->text OR $delivery->file) {
      $commands['delivery_start'] = 'запустить рассылку';
    }

    if ($delivery->text) {
      $commands['delivery_delete_text'] = 'очистить текст рассылки';
    }

    if ($delivery->file) {
      $commands['delivery_remove_file']    = 'открепить подключенный к рассылке файл';
      $commands['delivery_test_send_file'] = 'проверить отправку файла рассылки';
    }

    $commands['cancel'] = 'отменить создание рассылки';

    return $commands;
  }

  function commandDeliveryType() {
    if (!$this->checkForAdmin()) { return 'access denied'; }

    $this->user->state = 'delivery_type';
    $this->user->save();

    $buttons = [];
    foreach(DeliveryType::all() as $type) {
      $buttons[][] = "{$type->id}. {$type->name}";
    }

    return [
      'text'    => 'Пожалуйста, укажите тип вашей текущей рассылки. В зависимости от типа, она будет доставлена определённой группе подписчиков.',
      'buttons' => $buttons,
    ];
  }

  function commandDeliveryTypeMessages() {
    if (!$this->checkForAdmin()) { return 'access denied'; }

    // Попытаемся найти указанный тип рассылки
    preg_match("/(\\d)\\./u", $this->request->message->text, $matches);

    if (count($matches) > 1) {
      $type = DeliveryType::find($matches[1]);

      $delivery = $this->getLastDelivery();
      $delivery->type_id = $type->id;
      $delivery->save();

      $this->user->state = 'new_delivery';
      $this->user->save();

      return [
        'text'     => "Спасибо. Вашей рассылке установлен тип '{$type->name}'",
        'commands' => $this->delivery_commands($delivery),
      ];
    } else {
      return [
        'text'     => "Пожалуйста, выберите один из типов рассылок",
        'buttons'  => true,
      ];
    }

  }

  function commandNewDelivery() {
    if (!$this->checkForAdmin()) { return 'access denied'; }

    $this->user->state = 'new_delivery';
    $this->user->save();

    return [
      'text'     => "Вы собираетесь создать новую рассылку. Напишите сообщение, и оно будут сохранено для вашей рассылки. После этого вы сможете проверить её на корректность, а также инициализировать её отправку по адресатам.\n\nВы также имеете возможность прикреплять к рассылке файлы, видео, изображения и аудиозаписи.",
      'commands' => $this->delivery_commands($this->getLastDelivery()),
    ];
  }

  /**
   * Обработка сообщений в режиме подготовки к запуску новой рассылки
   */
  function commandNewDeliveryMessages() {
    if (!$this->checkForAdmin()) { return 'access denied'; }

    foreach([
      'photo'    => 'Отправленное вами фото сохранено для рассылки.',
      'document' => 'Отправленный вами документ сохранён для рассылки.',
      'voice'    => 'Отправленная вами звуковая запись сохранена для рассылки.',
      'video'    => 'Отправленное вами видео сохранено для рассылки.',
    ] as $type => $text) {
      if (property_exists($this->request->message, $type)) {
        //$debug = bot_debug($this->request);

        $delivery = $this->getLastDelivery();
        if ($type === 'photo') {
          $delivery->file = collect($this->request->message->photo)->last()->file_id;
        } else {
          $delivery->file = $this->request->message->$type->file_id;
        }
        $delivery->file_type = $type;
        $delivery->save();

        return [
          'text'     => $text,
          'commands' => $this->delivery_commands($delivery),
        ];
      }
    }

    if (property_exists($this->request->message, 'text')) {
      $delivery = $this->getLastDelivery();
      $delivery->text = $this->request->message->text;
      $delivery->save();

      return [
        'text'     => 'Спасибо, отправленный вами текст сохранён для рассылки.',
        'commands' => $this->delivery_commands($delivery),
      ];
    }

    return [
      'text'     => "Что-то непонятное вы нам отправили. К сожалению, я не знаю что это: " . bot_debug($this->request->message),
      'commands' => $this->delivery_commands($this->getLastDelivery()),
    ];

  }

  /**
   * Информация о текущей рассылке
   */
  function commandDeliveryInfo() {
    if (!$this->checkForAdmin()) { return 'access denied'; }

    $delivery = $this->getLastDelivery();
    $text  = "Ваша рассылка:\n";
    if ($delivery->text) {
      $text .= $delivery->text . "\n";
    } else {
      $text .= "Текст: отсутствует\n";
    }
    $text .= "Тип: {$delivery->type->name}\n";
    //$text .= "Статус: {$delivery->status->name}\n";
    $text .= "Файл: " . ($delivery->file
      ? $delivery->file_type === 'audio'
        ? 'аудиозапись'
        : $delivery->file_type === 'photo'
          ? 'изображение'
          : $delivery->file_type === 'video'
            ? 'видео'
            : 'документ'
      : 'отсутствует'
    );

    return [
      'text'     => $text,
      'commands' => $this->delivery_commands($delivery),
    ];
  }

  /**
   * Удалить прикреплённый файл
   */
  function commandDeliveryRemoveFile() {
    if (!$this->checkForAdmin()) { return 'access denied'; }

    $delivery = $this->getLastDelivery();
    if ($delivery->file) {
      $delivery->file      = null;
      $delivery->file_type = null;
      $delivery->save();

      return [
        'text'     => 'Подключённый вами файл был удалён из подготоваливаемой рассылки',
        'commands' => $this->delivery_commands($delivery),
      ];
    } else {
      return [
        'text'     => 'К вашей рассылке не подключён файл',
        'commands' => $this->delivery_commands($delivery),
      ];
    }
  }

  /**
   * Удалить текст рассылки
   */
  function commandDeliveryDeleteText() {
    if (!$this->checkForAdmin()) { return 'access denied'; }

    $delivery = $this->getLastDelivery();
    if ($delivery->text) {
      $delivery->text  = null;
      $delivery->save();

      return [
        'text'     => 'Текст рассылки был удалён.',
        'commands' => $this->delivery_commands($delivery),
      ];
    } else {
      return [
        'text'     => 'У вашей рассылки отсутствует текст.',
        'commands' => $this->delivery_commands($delivery),
      ];
    }
  }

  function commandDeliveryTestSendFile() {
    if (!$this->checkForAdmin()) { return 'access denied'; }

    $delivery = $this->getLastDelivery();
    return [
      'sendDocument' => $delivery,
    ];
  }

  /**
   * Запустить рассылку
   */
  function commandDeliveryStart() {
    $delivery = $this->getLastDelivery();
    
    $this->user->state = null;
    $this->user->save();

    dispatch(new TelegramDelivery($delivery));

    return [
      'text'     => 'Благодарим вас, рассылка была добавлена в очередь и вскоре будет отправлена всем подписчикам',
      'commands' => $this->delivery_commands($delivery),
    ];
  }

  /**
   * Возвращает созданную или создает новую рассылку
   */
  private function getLastDelivery() {
    $delivery = DeliveryModel::firstOrNew([
      'status_id' => DELIVERY_STATUS_WAITING,
      'author_id' => $this->user->id
    ]);
    if ($delivery->exists === false) {
      $delivery->status_id = DELIVERY_STATUS_WAITING;
      $delivery->type_id   = DeliveryType::first()->id;
      $delivery->author_id = $this->user->id;
      $delivery->save();
    }
    return $delivery;
  }


}
