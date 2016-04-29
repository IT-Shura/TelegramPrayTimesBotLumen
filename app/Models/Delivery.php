<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Delivery extends Model {

	protected $table = 'deliveries';

    protected $fillable = ['text','file','type_id','status_id','author_id'];

    function type() {
        return $this->belongsTo(DeliveryType::class,'type_id');
    }

    function status() {
        return $this->belongsTo(DeliveryStatus::class,'status_id');
    }

    function user() {
        return $this->belongsTo(User::class,'author_id');
    }

}
