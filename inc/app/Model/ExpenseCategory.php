<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class ExpenseCategory extends Model
{
    public function expenses(){
        return $this->hasMany(Expense::class, 'category_id', 'id');
    }
}
