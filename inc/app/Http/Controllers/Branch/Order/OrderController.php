<?php

namespace App\Http\Controllers\Branch\Order;

use Carbon\Carbon;
use App\Model\User;
use App\Model\UserDetail;
use App\Model\Cart;
use App\Model\Menu;
use App\Model\Order;
use App\Model\OrderDetail;
use App\Model\SubMenu;
use App\Model\Customer;
use App\Jobs\SendSMS;
use App\Http\Controllers\Controller;
use App\Model\MobileBank;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function allMenu(){
        $branch_id = Auth::user()->branch_id;
        $company_id = Auth::user()->company_id;
        //delete all queue 6 hours ago
        $countPrevQueues = Cart::where('created_at', '<', Carbon::parse('-6 hours'))->where(['company_id' => $company_id, 'branch_id' => $branch_id, 'status' => 1])->count();
        if($countPrevQueues > 0){
            Cart::where('created_at', '<', Carbon::parse('-6 hours'))->where(['company_id' => $company_id, 'branch_id' => $branch_id, 'status' => 1])->delete();
        }

        //delete all cart 6 hours ago
        $countCart = Cart::where('created_at', '<', Carbon::parse('-6 hours'))->where(['company_id' => $company_id, 'branch_id' => $branch_id, 'status' => 0])->count();
        if($countCart > 0){
            Cart::where('created_at', '<', Carbon::parse('-6 hours'))->where(['company_id' => $company_id, 'branch_id' => $branch_id, 'status' => 0])->delete();
        }

        //get all main menu with submenu
        $branch_menu_items = SubMenu::whereRaw("branch_id REGEXP $branch_id")->where('status', 1)->orderByRaw('LENGTH(menu_sl) asc')->orderBy('menu_sl', 'ASC')->get();
        $branch_main_menu = array();
        foreach($branch_menu_items as $item){
            $branch_main_menu[] = $item->main_menu;
        }
        $branch_menus = Menu::with('sub_menus')->whereIn('id', $branch_main_menu)->orderBy('menu_name', 'asc')->get();
    	return $branch_menus;
    }

    public function searchMenu(Request $request){
        $branch_id = Auth::user()->branch_id;
        $search_items = SubMenu::whereRaw("branch_id REGEXP $branch_id")->where('menu_sl', $request->menu_sl)->where('status', 1)->orderByRaw('LENGTH(menu_sl) asc')->orderBy('menu_sl', 'ASC')->get();
        return $search_items;
    }

    public function allQueue(){
        //get all queue
        $queues = Cart::whereRaw('Date(created_at) = CURDATE()')->where(['company_id' => Auth::user()->company_id, 'branch_id' => Auth::user()->branch_id, 'status' => 1])->groupBy('order_no')->get();
        return $queues;
    }

    public function maxOrder(){
        $branch_id = Auth::user()->branch_id;
        $company_id = Auth::user()->company_id;

        $queueCount = Cart::where(['company_id' => $company_id, 'branch_id' => $branch_id, 'status' => 1])->count();
        $orderCount = Order::where(['company_id' => $company_id, 'branch_id' => $branch_id])->count();

        if($orderCount > 0 && $queueCount > 0){
            $order = Order::whereRaw('Date(created_at) = CURDATE()')->where(['company_id' => $company_id, 'branch_id' => $branch_id])->max('daily_order_no');
            $queueOrder = Cart::whereRaw('Date(created_at) = CURDATE()')->where(['company_id' => $company_id, 'branch_id' => $branch_id, 'status' => 1])->max('order_no');
            if($order > $queueOrder){
                $order_no =  $order+1;
            }else {
                $order_no =  $queueOrder+1;
            }
        }elseif($orderCount > 0){
            $order = Order::whereRaw('Date(created_at) = CURDATE()')->where(['company_id' => $company_id, 'branch_id' => $branch_id])->max('daily_order_no');
            $order_no =  $order+1;
        }elseif($queueCount > 0){
            $order = Cart::whereRaw('Date(created_at) = CURDATE()')->where(['company_id' => $company_id, 'branch_id' => $branch_id, 'status' => 1])->max('order_no');
            $order_no =  $order+1;
        }else{
            $order = 0;
            $order_no =  $order+1;
        }

        $invNoCount = Order::where(['company_id' => $company_id])->max('inv_no');
        if($invNoCount > 0){
            $max_inv_no = Order::where(['company_id' => $company_id])->max('inv_no');
            $max_inv = $max_inv_no+1;
        }else {
            $max_inv = 445001;
        }

        return response()->json([
            'max_order' => $order_no,
            'max_inv' => $max_inv
        ]);
    }

    public function maxCustId(){
        $company_id = Auth::user()->company_id;
        $customerCount = Customer::where(['company_id' => $company_id])->count();
        if($customerCount > 0){
            $max_cust_id = Customer::where(['company_id' => $company_id])->max('cust_id');
            $max_customer_id = $max_cust_id+1;
        }else {
            $max_customer_id = 1;
        }

        return $max_customer_id;
    }

    public function vat(Request $request){
        $company_id = Auth::user()->company_id;
        $branch_id = Auth::user()->branch_id;
        $vat = MobileBank::where(['company_id' => $company_id, 'branch_id' => $branch_id, 'id' => $request->mbank])->first();
        return $vat->vat;
    }

    public function confirmOrder(Request $request){
        $company_id = Auth::user()->company_id;
        $branch_id = Auth::user()->branch_id;
        $customerCount = Customer::where(['company_id' => $company_id, 'phone' => $request->phone])->count();
        if($customerCount > 0){
            $cust = Customer::where(['company_id' => $company_id, 'phone' => $request->phone])->first();
            $cust->address = $request->address;
            $cust->save();
            $customer_id = $cust->cust_id;
        }else {
            $customerCount = Customer::where(['company_id' => $company_id])->count();
            if($customerCount > 0){
                $max_cust_id = Customer::where(['company_id' => $company_id])->max('cust_id');
                $cust_id = $max_cust_id+1;
            }else {
                $cust_id = 1;
            }

            $customer = new Customer();
            $customer->cust_id = $cust_id;
            $customer->create_by = Auth::user()->id;
            $customer->company_id = $company_id;
            $customer->branch_id = $branch_id;
            $customer->name = $request->cust_name;
            $customer->phone = $request->phone;
            $customer->address = $request->address;
            $customer->save();

            $customer_id = $customer->cust_id;
        }

        $cart_item = Cart::where(['company_id' => $company_id, 'branch_id' => $branch_id, 'order_no' => $request->order_no, 'status' => 0])->update(['table_no' => $request->table_no, 'customer_id' => $customer_id, 'emp_id' => $request->waiter, 'status' => 1]);

    }

    public function paidOrder(Request $request){
        $company_id = Auth::user()->company_id;
        $branch_id = Auth::user()->branch_id;

        $customerCount = Customer::where(['company_id' => $company_id, 'phone' => $request->phone])->count();
        if($customerCount > 0){
            $cust = Customer::where(['company_id' => $company_id, 'phone' => $request->phone])->first();
            $customer_id = $cust->cust_id;
        }else {
            $customerCount = Customer::where(['company_id' => $company_id])->count();
            if($customerCount > 0){
                $max_cust_id = Customer::where(['company_id' => $company_id])->max('cust_id');
                $cust_id = $max_cust_id+1;
            }else {
                $cust_id = 1;
            }

            $customer = new Customer();
            $customer->cust_id = $cust_id;
            $customer->create_by = Auth::user()->id;
            $customer->company_id = $company_id;
            $customer->branch_id = $branch_id;
            $customer->name = $request->cust_name;
            $customer->phone = $request->phone;
            $customer->address = $request->address;
            $customer->save();

            $customer_id = $customer->cust_id;
        }

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
        $order->customer_id = $customer_id;
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
        //         // dd($api_response);
        //     }
        // }

        $queue = Cart::where(['company_id' => $company_id, 'branch_id' => $branch_id, 'order_no' => $request->order_no])->delete();
    }

    public function checkCustById(Request $request){
        $customerCount = Customer::where(['cust_id' => $request->cust_id, 'company_id' => Auth::user()->company_id])->count();
        if($customerCount > 0){
            $customer = Customer::where(['cust_id' => $request->cust_id, 'company_id' => Auth::user()->company_id])->first();
            return response()->json([
                'status' => 200,
                'phone' => $customer->phone,
                'name' => $customer->name,
                'branch' => User::branch_name($customer->branch_id),
                'address' => $customer->address
            ]);
        }
    }

    public function checkCustByPhone(Request $request){
        $customerCount = Customer::where(['company_id' => Auth::user()->company_id, 'phone' => $request->phone])->count();
        if($customerCount > 0){
            $customer = Customer::where(['company_id' => Auth::user()->company_id, 'phone' => $request->phone])->first();
            return response()->json([
                'status' => 200,
                'cust_id' => $customer->cust_id,
                'name' => $customer->name,
                'branch' => User::branch_name($customer->branch_id),
                'address' => $customer->address
            ]);
        }
    }

    public function userDetail(){
        return UserDetail::where('user_id', Auth::user()->id)->first();
    }

    public function terms(){
        $company = UserDetail::where('user_id', Auth::user()->id)->first();
        if($company->terms != ''){
            $terms = $company->terms;
        }else {
            $company_id = User::where('company_id', Auth::user()->company_id)->first();
            $comDetail = UserDetail::where('user_id', $company_id->id)->first();
            $terms = $comDetail->terms;
        }
        return $terms;
    }

    public function userName(Request $request){
        $user = UserDetail::where('user_id', $request->id)->first();
        return  $user->name;
    }

}
