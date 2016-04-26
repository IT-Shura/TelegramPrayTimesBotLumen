<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class MessageHistory extends Model {

    protected $table = 'messaging_history';

    protected $fillable = ['id','users_id','user_message','answer'];

    /**
    * Типы подписок, с которыми связан пользователь
    */
    function user() {
        return $this->belongsTo('App\Models\User', 'users_id');
    }

}
