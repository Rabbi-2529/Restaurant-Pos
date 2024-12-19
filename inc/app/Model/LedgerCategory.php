<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class LedgerCategory extends Model
{
    public function ledgers(){
        return $this->hasMany(Ledger::class, 'category_id', 'id');
    }
}
