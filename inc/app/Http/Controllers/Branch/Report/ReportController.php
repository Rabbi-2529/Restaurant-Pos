<?php

namespace App\Http\Controllers\Branch\Report;

use Carbon\Carbon;
use App\Model\Customer;
use App\Model\SubMenu;
use App\Model\Order;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ReportController extends Controller
{
    public function todayReport(){
        if(Auth::user()->alt_user == 1){
            $orderCount = Order::with('order_items')->whereRaw('Date(created_at) = CURDATE()')->where(['company_id' => Auth::user()->company_id, 'branch_id' => Auth::user()->branch_id, 'status' => 1])->count();

            $limit = (25*$orderCount)/100;
            $limit = $limit+10;
            // dd($limit);

            $orders = Order::with('order_items', 'mobilebank')->whereRaw('Date(created_at) = CURDATE()')->where(['company_id' => Auth::user()->company_id, 'branch_id' => Auth::user()->branch_id, 'status' => 1])->orderBy('created_at', 'desc')->take($limit)->get();
        }else{
            $orders = Order::with('order_items', 'mobilebank')->whereRaw('Date(created_at) = CURDATE()')->where(['company_id' => Auth::user()->company_id, 'branch_id' => Auth::user()->branch_id, 'status' => 1])->orderBy('created_at', 'desc')->get();
            // dd($orders);
        }
        $order_id = Order::whereRaw('Date(created_at) = CURDATE()')->where(['company_id' => Auth::user()->company_id, 'branch_id' => Auth::user()->branch_id, 'status' => 1])->pluck('id');
        $totalItem = DB::table('order_details')
          ->select([
                'menu_name',
                DB::raw("SUM(qty) as total_qty"),
                DB::raw("SUM(price) as total_price"),
            ])
          ->groupBy('menu_name')
          ->whereIn('order_id', $order_id)
          ->get();
        // dd($totalItem);

        $all_sell_menu = DB::table('order_details')
        ->whereIn('order_id', $order_id)
        ->groupBy('menu_name')
        ->pluck('menu_name');

        $branch_id = Auth::user()->branch_id;
        $not_sell_menu = SubMenu::select('menu_name', 'price')->whereRaw("branch_id REGEXP $branch_id")->whereNotIn('menu_name', $all_sell_menu)->get();

        return response()->json([
            'orders' => $orders,
            'totalItem' => $totalItem,
            'not_sell_menu' => $not_sell_menu
        ]);
    }

    public function monthlyReport(){
        if(Auth::user()->alt_user == 1){
            $orderCount = Order::with('order_items')->where(['company_id' => Auth::user()->company_id, 'branch_id' => Auth::user()->branch_id, 'status' => 1])->whereDate('created_at', '>', Carbon::now()->subDays(30))->count();

            $limit = (25*$orderCount)/100;
            $limit = $limit+10;
            // dd($limit);

            $orders = Order::with('order_items', 'mobilebank')->where(['company_id' => Auth::user()->company_id, 'branch_id' => Auth::user()->branch_id, 'status' => 1])->whereDate('created_at', '>', Carbon::now()->subDays(30))->orderBy('created_at', 'desc')->take($limit)->get();
        }else{
            $orders = Order::with('order_items', 'mobilebank')->where(['company_id' => Auth::user()->company_id, 'branch_id' => Auth::user()->branch_id, 'status' => 1])->whereDate('created_at', '>', Carbon::now()->subDays(30))->orderBy('branch_order_no', 'desc')->get();
            // dd($orders);
        }

        $order_id = Order::where(['company_id' => Auth::user()->company_id, 'branch_id' => Auth::user()->branch_id, 'status' => 1])->whereDate('created_at', '>', Carbon::now()->subDays(30))->pluck('id');
        $totalItem = DB::table('order_details')
          ->select([
                'menu_name',
                DB::raw("SUM(qty) as total_qty"),
                DB::raw("SUM(price) as total_price"),
            ])
          ->groupBy('menu_name')
          ->whereIn('order_id', $order_id)
          ->orderBy(DB::raw("SUM(qty)"), 'desc')
          ->get();

          $all_sell_menu = DB::table('order_details')
          ->whereIn('order_id', $order_id)
          ->groupBy('menu_name')
          ->pluck('menu_name');

          $branch_id = Auth::user()->branch_id;
          $not_sell_menu = SubMenu::select('menu_name', 'price')->whereRaw("branch_id REGEXP $branch_id")->whereNotIn('menu_name', $all_sell_menu)->get();
          return response()->json([
            'orders' => $orders,
            'totalItem' => $totalItem,
            'not_sell_menu' => $not_sell_menu
          ]);
    }

    public function searchReport(Request $request){
        if($request->from != ''){
            $fromtime = strtotime($request->from);
            $from = date('Y-m-d H:i:s',$fromtime);
        }else{
            $from = false;
        }

        if($request->to != ''){
            $totime = strtotime($request->to);
            $to = date('Y-m-d H:i:s',$totime);
        }else{
            $to = false;
        }

        if($request->mbank != ''){
            $mbank = $request->mbank;
        }else{
            $mbank = false;
        }

        if($request->card != ''){
            $card = $request->card;
        }else{
            $card = false;
        }

      $orders = Order::with('order_items', 'mobilebank')->when($from, function ($query, $from) {
                    return $query->where('created_at', '>=', $from);
                })
                ->when($to, function ($query, $to) {
                    return $query->where('created_at', '<=', $to);
                })
                ->when($mbank, function ($query, $mbank) {
                    return $query->where('mbank', $mbank);
                })
                ->when($card, function ($query, $card) {
                    return $query->where('card', $card);
                });
                $orders = $orders->where(['company_id' => Auth::user()->company_id, 'branch_id' => Auth::user()->branch_id, 'status' => 1])->get();
      // dd($orders);

      return $orders;
    }

    public function searchReportToday(Request $request){
        if($request->mbank != ''){
            $mbank = $request->mbank;
        }else{
            $mbank = false;
        }

        if($request->card != ''){
            $card = $request->card;
        }else{
            $card = false;
        }

      $orders = Order::with('order_items', 'mobilebank')->when($mbank, function ($query, $mbank) {
                    return $query->where('mbank', $mbank);
                })
                ->when($card, function ($query, $card) {
                    return $query->where('card', $card);
                });
                $orders = $orders->whereRaw('Date(created_at) = CURDATE()')->where(['company_id' => Auth::user()->company_id, 'branch_id' => Auth::user()->branch_id, 'status' => 1])->get();
      // dd($orders);
      return $orders;
    }

    public function custInfo(Request $request){
        $customer = Customer::where(['company_id' => Auth::user()->company_id, 'branch_id' => Auth::user()->branch_id, 'cust_id' => $request->cust_id])->first();
        // dd($customer);
        return response()->json([
            'customer_name' => $customer->name,
            'customer_phone' => $customer->phone
        ]);
    }
}
