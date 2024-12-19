<?php

namespace App\Model;

use Auth;
use Illuminate\Database\Eloquent\Model;

class BankInfo extends Model
{
    public $timestamps = false;


    public function bank(){
        return $this->belongsTo(Bank::class, 'bank_id');
    }
}
