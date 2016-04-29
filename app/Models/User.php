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
    
    function getPrayTimes($date = null) {
        $prayTime = new \App\Helpers\PrayTime($this->method);
        $date     = $date ? $date->getTimestamp() : strtotime(date('Y-m-d'));
        
        return $prayTime->getPrayerTimes($date, $this->latitude, $this->longitude, $this->timezone);
    }
    
    function getTimezoneName() {
        $timezone = $this->timezone * -1;
        if ($timezone >= 0) { $timezone = '+' . ((string) $timezone); }
        $timezone = (string) $timezone;
        return "Etc/GMT{$timezone}";
    }

}
