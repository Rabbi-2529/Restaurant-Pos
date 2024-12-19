<?php

namespace App\Http\Controllers\Auth;

use Carbon\Carbon;
use App\Model\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use App\Http\Controllers\Controller;


class LoginController extends Controller
{
    public function login_process(Request $request){
        $alt_user = 0;
        if(is_numeric($request->email)){
            $getEmail = User::where('phone', $request->email)->first();
            if($getEmail){
                $email = $getEmail->email;
            }else{
                return response()->json([
                    'status' => 400,
                    'msg' => 'Login credential was wrong'
                ]);
            }
        }else{
            $email = $request->email;
            $alt_user = 1;
        }

        if (Auth::attempt(['email' => $email, 'password' => $request->password])) {

            if(Auth::user()->role == 3 || Auth::user()->role == 4){
                $expiry_date = User::expire_date(Auth::user()->company_id);
                $current_date = Carbon::now()->format('Y-m-d');
                if($current_date > $expiry_date){
                    Auth::logout();
                    Session::flush();
                    return response()->json([
                        'status' => 400,
                        'msg' => 'Your account is Expired'
                    ]);
                }
            }

            if(Auth::user()->status == 1){
                User::where('id', Auth::user()->id)->update(['alt_user' => $alt_user]);
                return response()->json([
                    'status' => 200,
                    'role' => Auth::user()->role
                ]);
            }elseif(Auth::user()->status == 2) {
                Auth::logout();
                Session::flush();
                return response()->json([
                    'status' => 400,
                    'msg' => 'Your account is suspended'
                ]);
            }
        }
    }

    public function logout(){
        User::where('id', Auth::user()->id)->update(['alt_user' => 0]);
        Auth::logout();
        Session::flush();
        return redirect('/');
    }
}
