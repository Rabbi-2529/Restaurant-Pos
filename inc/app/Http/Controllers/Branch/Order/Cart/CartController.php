<?php

namespace App\Http\Controllers\Branch\Order\Cart;

use Auth;
use Carbon\Carbon;
use App\Model\Cart;
use App\Model\Order;
use App\Model\Menu;
use App\Model\SubMenu;
use App\Model\Customer;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CartController extends Controller
{  
    public function addToCart(Request $request){
        $branch_id = Auth::user()->branch_id;
        $company_id = Auth::user()->company_id;
        $menu = SubMenu::where(['id' => $request->id, 'company_id' => $company_id])->first();
        $countCart = Cart::where(['company_id' => $company_id, 'branch_id' => $branch_id, 'menu_id' => $menu->id, 'status' => 0])->count();

        if($countCart > 0){
            $cart = Cart::where(['company_id' => $company_id, 'branch_id' => $branch_id, 'menu_id' => $menu->id, 'status' => 0])->first();
            $cart->qty += 1;
            $cart->price += $menu->price;
            $cart->save();
        }else{
            $cart = new Cart;
            $cart->company_id = $company_id;
            $cart->branch_id = $branch_id;
            $cart->order_no = $request->order_no;
            $cart->menu_id = $menu->id;
            $cart->menu_name = $menu->menu_name;
            $cart->qty = 1;
            $cart->price = $menu->price;
            $cart->save();
        }
    }

    public function removeCart(Request $request){
        $branch_id = Auth::user()->branch_id;
        $company_id = Auth::user()->company_id;
        $menu = SubMenu::whereRaw("branch_id REGEXP $branch_id")->where(['id' => $request->id, 'company_id' => $company_id])->first();

        $cartQty = Cart::where(['company_id' => $company_id, 'branch_id' => $branch_id, 'menu_id' => $menu->id, 'status' => 0])->first();

        if($cartQty->qty > 1){
            $cart = Cart::where(['company_id' => $company_id, 'branch_id' => $branch_id, 'menu_id' => $menu->id, 'status' => 0])->first();
            $cart->qty -= 1;
            $cart->price -= $menu->price;
            $cart->save();
        }
    }

    public function deleteCart(Request $request){
        $branch_id = Auth::user()->branch_id;
        $company_id = Auth::user()->company_id;
        $menu = SubMenu::whereRaw("branch_id REGEXP $branch_id")->where(['id' => $request->id, 'company_id' => $company_id])->first();

        $countCart = Cart::where(['company_id' => $company_id, 'branch_id' => Auth::user()->branch_id, 'menu_id' => $menu->id, 'status' => 0])->count();

        if($countCart > 0){
            $cart = Cart::where(['company_id' => $company_id, 'branch_id' => Auth::user()->branch_id, 'menu_id' => $menu->id, 'status' => 0])->first();
            $cart->delete();
        }
    }

    public function cartItem(){
        $cart_items = Cart::where(['company_id' => Auth::user()->company_id, 'branch_id' => Auth::user()->branch_id, 'status' => 0])->orderBy('id', 'desc')->get();
        return $cart_items;
    }

    public function totalCartItem(){
        $itemQty = Cart::where(['company_id' => Auth::user()->company_id, 'branch_id' => Auth::user()->branch_id, 'status' => 0])->sum('qty');
        return $itemQty;
    }
}
