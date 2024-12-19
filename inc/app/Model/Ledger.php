<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Ledger extends Model
{
    public function category(){
        return $this->hasOne(LedgerCategory::class, 'id', 'category_id');
    }
}
