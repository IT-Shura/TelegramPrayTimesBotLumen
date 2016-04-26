<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class User extends Model {

    protected $table = 'users';

    protected $fillable = ['id','name','family','nick','state','admin','superadmin','latitude','longitude','timezone'];

    /**
    * Типы подписок, с которыми связан пользователь
    */
    function deliveries_types() {
        return $this->belongsToMany('App\Models\DeliveryType', 'users_has_delivery_types', 'users_id', 'delivery_types_id');
    }

    /**
    * Возвращает имя пользователя или его ник, смотря что заполнено
    * @return string
    */
    function name() {
        if ($this->nick) {
            return '@'.$this->nick;
        }
        
        if ($this->name OR $this->family) {
            $text = '';
            if ($this->name) { $text .= $this->name; }
            if ($this->family) { $text . " {$this->family}"; }
            return $text;
        }
    }

}
