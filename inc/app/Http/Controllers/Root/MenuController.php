<?php

namespace App\Http\Controllers\Root;

use File;
use Auth;
use Carbon\Carbon;
use App\Model\Menu;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MenuController extends Controller
{   
    public function checkMenu(Request $request){
        // dd($request->all());
        $countMenu = Menu::where(['menu_name' => $request->menu_name])->count();
        if($countMenu > 0){
            return response()->json([
                'status' => 'exist'
            ]);
        }
    }


    public function checkMenuById(Request $request, $id){
        // dd($request->all());
        $countMenu = Menu::where('id', '!=', $id)->where(['menu_name' => $request->menu_name])->count();
        if($countMenu > 0){
            return response()->json([
                'status' => 'exist'
            ]);
        }
    }


    public function createMenu(Request $request){
        $validateData = $request->validate([
            'menu_name' => 'required'
        ]);

        $menu = new Menu();
        $menu->menu_name = $request->menu_name;
        $menu->status = '1';
        $menu->image = $request->image;
        $menu->save();
    }


    public function allMenu(){
        $menus = Menu::orderBy('menu_name', 'asc')->get();
    	return $menus;
    }


    public function editMenu($id){
        $menu = Menu::find($id);
        return $menu;
    }

    
    public function updateMenu(Request $request, $id){
        $validateData = $request->validate([
            'menu_name' => 'required'
        ]);

        $menu = Menu::find($id);
        $menu->menu_name = $request->menu_name;
        /*if has image save it*/
        if ($request->image != '') {
            if(File::exists('assets/uploads/menu_logo/'.$menu->image)){
                File::delete('assets/uploads/menu_logo/'.$menu->image);
            }
            $menu->image = $request->image;
        }
        $menu->save();
        
    }


    public function changeMenuStatus($id){
        $menu = Menu::find($id);
        // 1 = active, 2 = suspend
        if($menu->status == '1'){
            $menu->status = '2';
        }else{
            $menu->status = '1';
        }
        $menu->save();
    }


    public function deleteMenu($id){
        $menu = Menu::find($id);
        if(File::exists('assets/uploads/menu_logo/'.$menu->image)){
            File::delete('assets/uploads/menu_logo/'.$menu->image);
        }
        $menu->delete();
    }

    public function uploadMenuImage(Request $request){
        $filename = time().'.'.$request->file->extension();
        $request->file->move('assets/uploads/menu_logo', $filename);
        return $filename;
    }
    
    public function deleteMenuImage(Request $request){
        if(File::exists('assets/uploads/menu_logo/'.$request->image)){
            File::delete('assets/uploads/menu_logo/'.$request->image);
        }
    }

    public function deleteMenuImageFromServer(Request $request, $id){
        if(File::exists('assets/uploads/menu_logo/'.$request->image)){
            File::delete('assets/uploads/menu_logo/'.$request->image);
        }
        $menu = Menu::find($id);
        $menu->image = null;
        $menu->save();
    }
}
