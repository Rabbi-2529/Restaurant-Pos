<?php

namespace App\Http\Controllers;

use File;
use Auth;
use App\Model\User;
use App\Model\Cart;
use App\Model\UserDetail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index(Request $request){
        if (!Auth::check() && $request->path() != '/') {
            return redirect('/');
        }elseif(Auth::check() && $request->path() == '/'){
            if(Auth::user()->role == 1){
                return redirect('/root');
            }elseif(Auth::user()->role == 2){
                return redirect('/company');
            }elseif(Auth::user()->role == 3){
                return redirect('/branch');
            }
        }
        // return $request->path();
        return view('layout.root_master');
    }


    public function changePwd(Request $request){
        
        if(Hash::check($request->current_pwd, Auth::user()->getAuthPassword())){
            if($request->new_pwd == $request->confirm_pwd){
                User::where(['id' => Auth::user()->id])->update(['password' => Hash::make($request->new_pwd)]);
                UserDetail::where(['user_id' => Auth::user()->id])->update(['str_pass' => $request->new_pwd]);
                return response()->json([
                    'status' => 200,
                    'msg' => 'Password changed successfully.'
                ]);
            }else{
                return response()->json([
                    'status' => 400,
                    'msg' => 'New & confirm password should be same.'
                ]);
            }
        }else{
            return response()->json([
                'status' => 400,
                'msg' => 'Current password was wrong.'
            ]);
        }
        
    }

    public function userDetail(){
        return UserDetail::where('user_id', Auth::user()->id)->first();
    }

    public function updateProfile(Request $request){
        $id = Auth::user()->id;
        $user = User::find($id);
        $user->name = $request->name;
        $user->save();

        $userDet = UserDetail::where('user_id', $id)->first();
        $userDet->name = $request->name;
        $userDet->hotline = $request->hotline;
        $userDet->designation = $request->designation;
        $userDet->address = $request->address;
        if(Auth::user()->role == 2 || Auth::user()->role == 3){
            $userDet->terms = $request->terms;
            $userDet->vat_reg_no = $request->vat_reg_no;
        }
        $userDet->image = $request->image;
        $userDet->save();
    }


    public function uploadProfileImage(Request $request){
        $filename = time().'.'.$request->file->extension();
        $request->file->move('assets/uploads/user_logo', $filename);
        return $filename;
    }

    public function deleteProfileImage(Request $request){
        $id = Auth::user()->id;
        if(File::exists('assets/uploads/user_logo/'.$request->image)){
            File::delete('assets/uploads/user_logo/'.$request->image);
        }
        $employeeDetail = UserDetail::where('user_id', $id)->first();
        $employeeDetail->image = null;
        $employeeDetail->save();
    }
}
