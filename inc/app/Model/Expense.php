<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    public function category(){
        return $this->hasOne(ExpenseCategory::class, 'id', 'category_id');
    }
}
