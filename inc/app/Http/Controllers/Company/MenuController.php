<?php

namespace App\Http\Controllers\Company;

use File;
use Auth;
use Carbon\Carbon;
use App\Model\Menu;
use App\Model\SubMenu;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MenuController extends Controller
{
    public function mainMenu(){
        $mainMenu = Menu::where('status', 1)->orderBy('menu_name', 'ASC')->get();
        return $mainMenu;
    }

    // public function menuList(){
    //     $branch_id = Auth::user()->branch_id;
    //     $branch_menu = SubMenu::whereRaw("branch_id REGEXP $branch_id")->where('status', 1)->orderByRaw('LENGTH(menu_sl) asc')->orderBy('menu_sl', 'ASC')->get();
    //     // $main_menu = array();
    //     // foreach($branch_menu as $item){
    //     //     $main_menu[] = $item->main_menu;
    //     // }
    //     // $mainMenu = Menu::whereIn('id', $main_menu)->get();
    //     dd($branch_menu);
    // }


    public function checkMenu(Request $request){
        // dd($request->all());
        $countMenu = SubMenu::where(['company_id' => Auth::user()->company_id, 'menu_name' => $request->menu_name])->count();
        if($countMenu > 0){
            return response()->json([
                'status' => 'exist'
            ]);
        }
    }


    public function checkMenuSerial(Request $request){
        $countMenuSl = SubMenu::where(['company_id' => Auth::user()->company_id, 'menu_sl' => $request->menu_sl])->count();
        if($countMenuSl > 0){
            return response()->json([
                'status' => 'exist'
            ]);
        }
    }


    public function checkMenuById(Request $request, $id){
        // dd($request->all());
        $countMenu = SubMenu::where('id', '!=', $id)->where(['company_id' => Auth::user()->company_id, 'menu_name' => $request->menu_name])->count();
        if($countMenu > 0){
            return response()->json([
                'status' => 'exist'
            ]);
        }
    }

    public function checkMenuSlById(Request $request, $id){
        // dd($request->all());
        $countMenuSl = SubMenu::where('id', '!=', $id)->where(['company_id' => Auth::user()->company_id, 'menu_sl' => $request->menu_sl])->count();
        if($countMenuSl > 0){
            return response()->json([
                'status' => 'exist'
            ]);
        }
    }


    public function createMenu(Request $request){
        $countMenuSl = SubMenu::where(['company_id' => Auth::user()->company_id, 'menu_sl' => $request->menu_sl])->count();

        $countMenu = SubMenu::where(['company_id' => Auth::user()->company_id, 'menu_name' => $request->menu_name])->count();

        if($countMenuSl > 0){
            return response()->json([
                'status' => 400,
                'msg' => 'Serial no already exists.'
            ]);
        }elseif($countMenu > 0){
            return response()->json([
                'status' => 400,
                'msg' => 'Menu already exists.'
            ]);
        }else{
            $menu = new SubMenu();
            $menu->company_id = Auth::user()->company_id;
            $menu->menu_sl = $request->menu_sl;
            $menu->main_menu = $request->main_menu;
            $menu->menu_name = $request->menu_name;
            $menu->kitchen_no = $request->kitchen_no;
            $menu->ingredient = $request->ingredient;
            $menu->cost = $request->cost;
            $menu->price = $request->price;
            $menu->status = '1';
            $menu->save();
            return response()->json([
                'status' => 200,
                'msg' => 'Menu created successfully.'
            ]);
        }
    }


    public function allMenu(){
        $menus = SubMenu::where('company_id', Auth::user()->company_id)->orderBy('menu_name', 'asc')->get();
    	return $menus;
    }


    public function editMenu($id){
        $menu = SubMenu::find($id);
        return $menu;
    }


    public function updateMenu(Request $request, $id){
        $countMenuSl = SubMenu::where(['company_id' => Auth::user()->company_id, 'menu_sl' => $request->menu_sl])->where('id', '!=', $id)->count();
        $countMenu = SubMenu::where(['company_id' => Auth::user()->company_id, 'menu_name' => $request->menu_name])->where('id', '!=', $id)->count();

        if($countMenuSl > 0){
            return response()->json([
                'status' => 400,
                'msg' => 'Serial no already exists.'
            ]);
        }else if($countMenu > 0){
            return response()->json([
                'status' => 400,
                'msg' => 'Menu already exists.'
            ]);
        }else{
            $menu = SubMenu::find($id);
            $menu->main_menu = $request->main_menu;
            $menu->menu_sl = $request->menu_sl;
            $menu->menu_name = $request->menu_name;
            $menu->kitchen_no = $request->kitchen_no;
            $menu->ingredient = $request->ingredient;
            $menu->cost = $request->cost;
            $menu->price = $request->price;
            $menu->save();
            return response()->json([
                'status' => 200,
                'msg' => 'Menu Updated successfully.'
            ]);
        }
    }


    public function changeMenuStatus($id){
        $menu = SubMenu::find($id);
        // 1 = active, 2 = suspend
        if($menu->status == '1'){
            $menu->status = '2';
        }else{
            $menu->status = '1';
        }
        $menu->save();
    }


    // public function deleteMenu($id){
    //     $menu = SubMenu::find($id);
    //     $menu->delete();
    // }

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


    public function showSubMenu(Request $request){
        $sub_menus = SubMenu::where(['company_id' => Auth::user()->company_id, 'main_menu' => $request->menu_id, 'status' => 1])->orderBy('menu_name', 'asc')->get();
    	return $sub_menus;
    }


    public function assignMenu(Request $request){
        // dd($request->all());
        $submenu = SubMenu::where('id', $request->menu_id)->first();
        $branch = $request->branch_id;

        if (!empty($submenu->branch_id)) {
            if(in_array($branch, explode('-',$submenu->branch_id))){
                $branch_id = explode('-', $submenu->branch_id);
                if(($key = array_search($branch, $branch_id)) !== false){
                    unset($branch_id[$key]);
                    $submenu = SubMenu::where('id', $request->menu_id)->first();
                    $submenu->branch_id = implode('-', $branch_id);
                }
            }else{
                $submenu->branch_id = $submenu->branch_id.'-'.$branch;
            }
        }else {
            $submenu->branch_id = $branch;
        }
        $submenu->save();
    }
}
