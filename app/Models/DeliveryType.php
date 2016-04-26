<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class DeliveryType extends Model {

	protected $table = 'deliveries_types';

    function users() {
        return $this->belongsToMany(User::class, 'users_has_delivery_types', 'delivery_types_id', 'users_id');
    }

}
