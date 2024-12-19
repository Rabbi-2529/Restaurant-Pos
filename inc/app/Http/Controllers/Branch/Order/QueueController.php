<?php

namespace App\Http\Controllers\Branch\Order;

use Carbon\Carbon;
use App\Model\User;
use App\Model\Cart;
use App\Model\MobileBank;
use App\Model\Order;
use App\Model\OrderDetail;
use App\Model\SubMenu;
use App\Model\Customer;
use App\Jobs\SendSMS;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class QueueController extends Controller
{
    public function cartItem(Request $request){
        $cart_items = Cart::where(['order_no' => $request->order_no, 'customer_id' => $request->cust_id, 'company_id' => Auth::user()->company_id, 'branch_id' => Auth::user()->branch_id, 'status' => 1])->get();

        return $cart_items;
    }

    public function totalCartItem(Request $request){
        $itemQty = Cart::where(['order_no' => $request->order_no, 'customer_id' => $request->cust_id,'company_id' => Auth::user()->company_id, 'branch_id' => Auth::user()->branch_id, 'status' => 1])->sum('qty');
        return $itemQty;
    }

    public function custInfo(Request $request){
        $customer = Customer::where(['cust_id' => $request->cust_id, 'company_id' => Auth::user()->company_id, 'branch_id' => Auth::user()->branch_id])->orderBy('name', 'asc')->first();
        return $customer;
    }

    public function addToCart(Request $request){
        // dd($request->all());
        $branch_id = Auth::user()->branch_id;
        $company_id = Auth::user()->company_id;
        $menu = SubMenu::where(['id' => $request->id, 'company_id' => $company_id])->first();
        $countCart = Cart::where(['company_id' => $company_id, 'branch_id' => $branch_id, 'menu_id' => $menu->id, 'customer_id' => $request->cust_id, 'order_no' => $request->order_no, 'status' => 1])->count();

        if($countCart > 0){
            $cart = Cart::where(['company_id' => $company_id, 'branch_id' => $branch_id, 'menu_id' => $menu->id, 'customer_id' => $request->cust_id, 'order_no' => $request->order_no, 'status' => 1])->first();
            $cart->qty += 1;
            $cart->price += $menu->price;
            $cart->save();
        }else{
            $cartDet = Cart::where(['company_id' => $company_id, 'branch_id' => $branch_id, 'customer_id' => $request->cust_id, 'order_no' => $request->order_no, 'status' => 1])->first();

            $cart = new Cart;
            $cart->company_id = $company_id;
            $cart->branch_id = $branch_id;
            $cart->order_no = $request->order_no;
            $cart->menu_id = $menu->id;
            $cart->customer_id = $request->cust_id;
            $cart->emp_id = $cartDet->emp_id;
            $cart->table_no = $cartDet->table_no;
            $cart->menu_name = $menu->menu_name;
            $cart->qty = 1;
            $cart->price = $menu->price;
            $cart->status = 1;
            $cart->save();
        }
    }

    public function removeCart(Request $request){
        $branch_id = Auth::user()->branch_id;
        $company_id = Auth::user()->company_id;
        $menu = SubMenu::whereRaw("branch_id REGEXP $branch_id")->where(['id' => $request->id, 'company_id' => $company_id])->first();

        $cartQty = Cart::where(['company_id' => $company_id, 'branch_id' => $branch_id, 'menu_id' => $menu->id, 'customer_id' => $request->cust_id, 'order_no' => $request->order_no, 'status' => 1])->first();

        if($cartQty->qty > 1){
            $cart = Cart::where(['company_id' => $company_id, 'branch_id' => $branch_id, 'menu_id' => $menu->id, 'customer_id' => $request->cust_id, 'order_no' => $request->order_no, 'status' => 1])->first();
            $cart->qty -= 1;
            $cart->price -= $menu->price;
            $cart->save();
        }
    }

    public function deleteCart(Request $request){
        $branch_id = Auth::user()->branch_id;
        $company_id = Auth::user()->company_id;
        $menu = SubMenu::whereRaw("branch_id REGEXP $branch_id")->where(['id' => $request->id, 'company_id' => $company_id])->first();

        $countCart = Cart::where(['company_id' => $company_id, 'branch_id' => $branch_id, 'menu_id' => $menu->id, 'customer_id' => $request->cust_id, 'order_no' => $request->order_no, 'status' => 1])->count();

        if($countCart > 0){
            $cart = Cart::where(['company_id' => $company_id, 'branch_id' => $branch_id, 'menu_id' => $menu->id, 'customer_id' => $request->cust_id, 'order_no' => $request->order_no, 'status' => 1])->first();
            $cart->delete();
        }
    }

    public function paidOrder(Request $request){
        $company_id = Auth::user()->company_id;
        $branch_id = Auth::user()->branch_id;

        $order_time = Cart::where(['company_id' => $company_id, 'branch_id' => $branch_id, 'order_no' => $request->order_no])->orderBy('id', 'asc')->first();

        $invNoCount = Order::where(['company_id' => $company_id])->max('inv_no');
        if($invNoCount > 0){
            $max_inv_no = Order::where(['company_id' => $company_id])->max('inv_no');
            $inv_no = $max_inv_no+1;
        }else {
            $inv_no = 445001;
        }

        $companyOrderCount = Order::where(['company_id' => $company_id])->count();
        if($companyOrderCount > 0){
            $max_com_order_no = Order::where(['company_id' => $company_id])->max('com_order_no');
            $com_order_no = $max_com_order_no+1;
        }else {
            $com_order_no = 1;
        }

        $branchOrderCount = Order::where(['company_id' => $company_id, 'branch_id' => $branch_id])->count();

        if($branchOrderCount > 0){
            $max_branch_order_no = Order::where(['company_id' => $company_id, 'branch_id' => $branch_id])->max('branch_order_no');
            $branch_order_no = $max_branch_order_no+1;
        }else {
            $branch_order_no = 1;
        }

        $total = str_replace(',', '', $request->total_amount);
        if(!empty($request->mbank)){
            $mbank = MobileBank::where('id', $request->mbank)->first();
            $vat = $mbank->vat;

            // $total_amount = str_replace(',', '', $request->total_amount);
            $vat = ($total*$vat) / 100;
            // $total = $request->total_amount + $vat;
        }

        $order = new Order();
        $order->com_order_no = $com_order_no;
        $order->inv_no = $inv_no;
        $order->branch_order_no = $branch_order_no;
        $order->daily_order_no = $request->order_no;
        $order->create_by = Auth::user()->id;
        $order->company_id = $company_id;
        $order->branch_id = $branch_id;
        $order->customer_id = $request->cust_id;
        $order->emp_id = $request->waiter;
        $order->table_no = $request->table_no;

        $order->sub_total = $request->sub_total;
        $order->discount = $request->discount;
        $order->delivery = $request->delivery;
        $order->total = $total;
        $order->mbank = $request->mbank;
        $order->card = $request->card;
        $order->created_at = $order_time->created_at;
        $order->save();

        $queues = Cart::where(['company_id' => $company_id, 'branch_id' => $branch_id, 'order_no' => $request->order_no])->get();

        foreach ($queues as $queue) {
            $orderDetail = new OrderDetail();
            $orderDetail->order_id = $order->id;
            $orderDetail->menu_id = $queue->menu_id;
            $orderDetail->menu_name = $queue->menu_name;
            $orderDetail->qty = $queue->qty;
            $orderDetail->price = $queue->price;
            $orderDetail->save();
        }

        if (in_array('5', explode('-', Auth::user()->permission))) {
            $phone = $request->phone;
            $order_no = $request->order_no;
            $total_amount = $request->total_amount;
            $company_id = Auth::user()->company_id;
            $api_key = Auth::user()->api_key;
            $sender_id = Auth::user()->sender_id;
            SendSMS::dispatch($phone, $order_no, $total_amount, $company_id, $api_key, $sender_id)->delay(now()->addSeconds(5));
        }

        // if (in_array('5', explode('-', Auth::user()->permission))) {
        //     $company = User::where(['company_id' => Auth::user()->company_id, 'role' => 2])->first();
        //     if(Auth::user()->api_key != '' && Auth::user()->sender_id != '' || $company->api_key != '' && $company->sender_id != ''){
        //         if (Auth::user()->api_key != '' && Auth::user()->sender_id != '') {
        //             $api_key = Auth::user()->api_key;
        //             $sender_id = Auth::user()->sender_id;
        //         } else {
        //             if ($company->api_key != '' && $company->sender_id != '') {
        //                 $api_key = $company->api_key;
        //                 $sender_id = $company->sender_id;
        //             }
        //         }

        //         $mobile_number= $request->phone;
        //         $message = 'Your order no is: #00'.$request->order_no.'. Total Amount is: '.$request->total_amount.'Tk. Thank you.';
        //         $message = urlencode($message);
        //         // $api_key = "445156057064961560570649";
        //         $client = new \GuzzleHttp\Client();
        //         $api_url = "http://sms.iglweb.com/api/v1/send?api_key=". $api_key ."&contacts=". $mobile_number ."&senderid=". $sender_id ."&msg=".$message;
        //         $response = $client->request('GET', "$api_url");
        //         // dd($api_url);
        //         $json_response = $response->getBody()->getContents();
        //         $api_response = json_decode($json_response);
        //     }
        // }

        $queue = Cart::where(['company_id' => $company_id, 'branch_id' => $branch_id, 'order_no' => $request->order_no])->delete();
    }

    public function cencelOrder(Request $request){
        $company_id = Auth::user()->company_id;
        $branch_id = Auth::user()->branch_id;

        $order_time = Cart::where(['company_id' => $company_id, 'branch_id' => $branch_id, 'order_no' => $request->order_no])->orderBy('id', 'asc')->first();

        $invNoCount = Order::where(['company_id' => $company_id])->max('inv_no');
        if($invNoCount > 0){
            $max_inv_no = Order::where(['company_id' => $company_id])->max('inv_no');
            $inv_no = $max_inv_no+1;
        }else {
            $inv_no = 445001;
        }

        $companyOrderCount = Order::where(['company_id' => $company_id])->count();
        if($companyOrderCount > 0){
            $max_com_order_no = Order::where(['company_id' => $company_id])->max('com_order_no');
            $com_order_no = $max_com_order_no+1;
        }else {
            $com_order_no = 1;
        }

        $branchOrderCount = Order::where(['company_id' => $company_id, 'branch_id' => $branch_id])->count();

        if($branchOrderCount > 0){
            $max_branch_order_no = Order::where(['company_id' => $company_id, 'branch_id' => $branch_id])->max('branch_order_no');
            $branch_order_no = $max_branch_order_no+1;
        }else {
            $branch_order_no = 1;
        }

        $order = new Order();
        $order->com_order_no = $com_order_no;
        $order->inv_no = $inv_no;
        $order->branch_order_no = $branch_order_no;
        $order->daily_order_no = $request->order_no;
        $order->create_by = Auth::user()->id;
        $order->company_id = $company_id;
        $order->branch_id = $branch_id;
        $order->customer_id = $request->cust_id;
        $order->emp_id = $request->waiter;
        $order->table_no = $request->table_no;

        $order->sub_total = $request->sub_total;
        $order->discount = $request->discount;
        $order->delivery = $request->delivery;
        $order->total = str_replace(',', '', $request->total_amount);
        $order->pay_mtd = $request->pay_mtd;
        $order->status = 0;
        $order->created_at = $order_time->created_at;
        $order->save();

        $queues = Cart::where(['company_id' => $company_id, 'branch_id' => $branch_id, 'order_no' => $request->order_no])->get();

        foreach ($queues as $queue) {
            $orderDetail = new OrderDetail();
            $orderDetail->order_id = $order->id;
            $orderDetail->menu_id = $queue->menu_id;
            $orderDetail->menu_name = $queue->menu_name;
            $orderDetail->qty = $queue->qty;
            $orderDetail->price = $queue->price;
            $orderDetail->remark = $request->remark;
            $orderDetail->save();
        }

        $queue = Cart::where(['company_id' => $company_id, 'branch_id' => $branch_id, 'order_no' => $request->order_no])->delete();
    }

    public function paidBill(Request $request){
        $cart = Cart::where(['company_id' => Auth::user()->company_id, 'branch_id' => Auth::user()->branch_id, 'order_no' => $request->order_no, 'status' => 1])->update(['unpaid' => 1]);
    }

}
