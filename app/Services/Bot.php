<?php

namespace App\Services;

use Curl\Curl;

/**
 * Класс работы бота для программы "Телеграмм"
 * для шуры мусульман
 */
class Bot {

    /**
     * Список классов и команд, за которые они отвечают в данном боте
     */
    private $routing = [
        'Main' => [
            'start',
            'help',
            'cancel',
        ],
        'Namaz' => [
            'location',
            'namaz',
            'select_method',
        ],
    ];
    
    private $key;
    
    function __construct() {
        $this->key = env('TELEGRAM_KEY');
    }

    function processCommand($request, $user) {
        $this->request = $request;
        $this->user    = $user;

        if (property_exists($this->request->message, 'text')) {
            $command = str_replace('/','', explode(' ', mb_strtolower($this->request->message->text))[0]);
            foreach($this->routing as $class => $commands) {
                if (in_array($command,$commands)) {
                    $processor_name = self::class . '\\' . $class;
                    $processor = new $processor_name($request, $user, $this);

                    $function_name = 'command' . studly_case($command);

                    if (method_exists($processor, $function_name)) {
                        $this->sendAnswer($this->createReply($processor->$function_name()));
                        return;
                    }
                }
            }
        }

        // Возможно, есть возможность вытащить текущую команду на основе текущего состояния пользователя
        foreach($this->routing as $class => $commands) {
            if (in_array($this->user->state,$commands)) {

                $processor_name = self::class . '\\' . $class;
                $processor = new $processor_name($request, $user, $this);

                $function_name = 'command' . studly_case($this->user->state) . 'Messages';
                if (method_exists($processor, $function_name)) {
                    $this->sendAnswer($this->createReply($processor->$function_name()));
                    return;
                }
            }
        }

        $this->sendAnswer($this->createReply('Прошу прощения, но я не понял, что вам от меня нужно. Пожалуйста, постарайтесь уточнить ваш запрос.'));
    }

    /**
     * Вернуть определённым образом сформированный текст пользователю
     * @param string $answer
     * @return array
     */
    public function createReply($answer) {
        $method = 'sendMessage';
        $completed_answer = [ 'text' => '' ];

        if (is_string($answer)) {
            $completed_answer['text'] = (string) $answer;
        } else {
            // Добавим текст, если он был указан
            if (isset($answer['text'])) {
                $completed_answer['text'] = $answer['text'];
            }

            /*if (isset($answer['markdown'])) {
                $completed_answer['parse_mode'] = 'Markdown';
            }*/

            // Дополним текст доступными командами для продолжения работы
            if (isset($answer['commands'])) {
                if ($completed_answer['text']) {
                    $completed_answer['text'] .= "\n\n";
                }
                foreach($answer['commands'] as $command => $desc) {
                    $completed_answer['text'] .= "/{$command} - {$desc}\n";
                }
            }

            // Если была передана команда отправки документа - сделаем это
            if (isset($answer['sendDocument'])) {
                unset($completed_answer['text']);
                switch($answer['sendDocument']->file_type) {
                    case 'photo'        : $method = 'sendPhoto';        $file_key = 'photo';        break;
                    case 'document'     : $method = 'sendDocument';     $file_key = 'document';     break;
                    case 'voice'        : $method = 'sendAudio';        $file_key = 'audio';        break;
                    case 'video'        : $method = 'sendVideo';        $file_key = 'video';        break;
                }
                $completed_answer[$file_key] = $answer['sendDocument']->file;
            }
        }

        if (is_array($answer) and isset($answer['buttons']) and is_array($answer['buttons'])) {
            $completed_answer['reply_markup'] = json_encode([
                'keyboard'            => $answer['buttons'],
                //'one_time_keyboard' => true,
                'resize_keyboard'     => true,
            ]);
        } elseif (!isset($answer['buttons'])) {
            $completed_answer['reply_markup'] = json_encode([ 'hide_keyboard' => true ]);
        }

        return [
            'message' => $completed_answer,
            'method'    => $method,
        ];
    }

    /**
     * Отправляет ответное сообщение пользователю, написавшему боту
     */
    public function sendAnswer($answer, $user_id = null) {
        $curl = new Curl();

        if (empty($user_id)) { $user_id = $this->user->id; }

        $params = array_merge([
            'chat_id' => $user_id,
            /*'disable_web_page_preview' => true,*/
        ], $answer['message']);

        if ($this->request AND property_exists($this->request->message, 'text') AND isset($params['text'])) {
            \App\Models\MessageHistory::create([
                'users_id'     => $user_id,
                'user_message' => $this->request->message->text,
                'answer'       => $params['text'],
            ]);
        }

        $curl->get("https://api.telegram.org/bot{$this->key}/{$answer['method']}", $params);
    }

}