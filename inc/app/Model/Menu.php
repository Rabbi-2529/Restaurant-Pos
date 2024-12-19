<?php

namespace App\Model;

use Auth;
use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    public function sub_menus(){
        $branch_id = Auth::user()->branch_id;
        return $this->hasMany(SubMenu::class, 'main_menu')->whereRaw("branch_id REGEXP $branch_id");
    }
}
