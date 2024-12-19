<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Card extends Model
{
    public function bank(){
        return $this->belongsTo(Bank::class, 'bank_id');
    }
}
