<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class NamazNotification extends Model {

    protected $table = 'namaz_notifications';

    protected $fillable = ['id','users_id','date','namaz_type','minutes'];

    /**
    * Типы подписок, с которыми связан пользователь
    */
    function user() {
        return $this->belongsTo('App\Models\User', 'users_id');
    }

}
