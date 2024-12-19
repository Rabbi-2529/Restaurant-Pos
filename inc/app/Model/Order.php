<?php

namespace App\Model;

use Auth;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    public function order_items(){
        return $this->hasMany(OrderDetail::class, 'order_id');
    }

    public function mobilebank(){
        return $this->belongsTo(MobileBank::class, 'mbank');
    }
}


