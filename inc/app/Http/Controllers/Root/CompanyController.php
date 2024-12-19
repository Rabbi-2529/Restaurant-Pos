<?php

namespace App\Http\Controllers\Root;

use File;
use Auth;
use Carbon\Carbon;
use App\Model\User;
use App\Model\UserDetail;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CompanyController extends Controller
{   
    public function checkEmail(Request $request){
        // dd($request->email);
        $countUser = User::where('email', $request->email)->count();
        if($countUser > 0){
            return response()->json([
                'status' => 'exist'
            ]);
        }
    }


    public function checkPhone(Request $request){
        // dd($request->phone);
        $countUser = User::where('phone', $request->phone)->count();
        if($countUser > 0){
            return response()->json([
                'status' => 'exist'
            ]);
        }
    }


    public function checkEmailById(Request $request, $id){
        // dd($request->email);
        $countUser = User::where('id', '!=', $id)->where('email', $request->email)->count();
        if($countUser > 0){
            return response()->json([
                'status' => 'exist'
            ]);
        }
    }

    public function checkPhoneById(Request $request, $id){
        // dd($request->phone);
        $countUser = User::where('id', '!=', $id)->where('phone', $request->phone)->count();
        if($countUser > 0){
            return response()->json([
                'status' => 'exist'
            ]);
        }
    }


    public function createCompany(Request $request){
        $checkEmail = User::where('email', $request->email)->count();
        $checkPhone = User::where('phone', $request->phone)->count();
        if($checkEmail > 0){
            return response()->json([], 400);
        }elseif($checkPhone > 0){
            return response()->json([], 401);
        }else{
            $comCount = User::where('role', '2')->count();
            if($comCount > 0){
                $pre_max_com_id = User::where('company_id', '!=', '')->max('company_id');
                $new_com_id = $pre_max_com_id + 1;
            }else{
                $pre_max_com_id = 26672690;
                $new_com_id = $pre_max_com_id + 1;
            }

            /*insert company information*/
            $company = new User();
            $company->company_id = $new_com_id;
            $company->create_by = Auth::user()->id;
            $company->company_name = $request->company_name;
            $company->name = $request->name;
            $company->email = $request->email;
            $company->phone = $request->phone;
            $company->password = Hash::make($request->password);
            $company->status = '1';
            $company->role = '2';
            $company->expiry_date = date('Y-m-d', strtotime(Carbon::now(). ' +30 days'));
            $company->save();
            
            $companyDetail = new UserDetail();
            $companyDetail->user_id =  $company->id;
            $companyDetail->name = $request->name;
            $companyDetail->designation = $request->designation;
            $companyDetail->str_pass = $request->password;
            $companyDetail->address = $request->address;
            $companyDetail->image = $request->image;
            $companyDetail->save();
        }
    }


    public function allCompany(){
        $companies = User::where('role', '2')->orderBy('id', 'desc')->get();
    	return $companies;
    }


    public function editCompany($id){
        $company = User::find($id);
        $companyDetail = UserDetail::where('user_id', $id)->first();
        return response()->json([
            'company' => $company,
            'companyDetail' => $companyDetail
        ]);
    }

    
    public function updateCompany(Request $request, $id){

        $validateData = $request->validate([
            'company_name' => 'required',
            'name' => 'required',
            'email' => 'required|email',
            'phone' => 'required',
        ]);

        $company = User::find($id);
        $company->company_name = $request->company_name;
        $company->name = $request->name;
        $company->email = $request->email;
        $company->phone = $request->phone;
        if(!empty($request->password)){
            $company->password = Hash::make($request->password);
        }
        if(!empty($request->expiry_date)){
            $date = Carbon::parse($request->expiry_date);
            $expiry_date = $date->format('Y-m-d');
            $company->expiry_date = $expiry_date;
        }
        $company->save();

        $companyDetail = UserDetail::where('user_id', $id)->first();
        $companyDetail->name = $request->name;
        $companyDetail->designation = $request->designation;
        if(!empty($request->password)){
            $companyDetail->str_pass = $request->password;
        }
        $companyDetail->address = $request->address;
        /*if has image save it*/
        if ($request->image != '') {
            if(File::exists('assets/uploads/user_logo/'.$companyDetail->image)){
                File::delete('assets/uploads/user_logo/'.$companyDetail->image);
            }
            $companyDetail->image = $request->image;
        }
        $companyDetail->save();
        
    }


    public function changeComStatus($id){
        $company = User::find($id);
        // 1 = active, 2 = suspend
        if($company->status == '1'){
            $company->status = '2';
        }else{
            $company->status = '1';
        }
        $company->save();
    }


    public function deleteCompany($id){
        $company = User::find($id);
        $companyDetail = UserDetail::where('user_id', $id)->first();
        if(File::exists('assets/uploads/user_logo/'.$companyDetail->image)){
            File::delete('assets/uploads/user_logo/'.$companyDetail->image);
        }
        $company->delete();
    }

    public function uploadComImage(Request $request){
        $filename = time().'.'.$request->file->extension();
        $request->file->move('assets/uploads/user_logo', $filename);
        return $filename;
    }
    
    public function deleteComImage(Request $request){
        if(File::exists('assets/uploads/user_logo/'.$request->image)){
            File::delete('assets/uploads/user_logo/'.$request->image);
        }
    }

    public function deleteComImageFromServer(Request $request, $id){
        if(File::exists('assets/uploads/user_logo/'.$request->image)){
            File::delete('assets/uploads/user_logo/'.$request->image);
        }
        $companyDetail = UserDetail::where('user_id', $id)->first();
        $companyDetail->image = null;
        $companyDetail->save();
    }

    public function updateComPermission(Request $request, $id){
        $company = User::find($id);
        $permit_id = $request->permit_id;

        if (!empty($company->permission)) {
            if(in_array($permit_id, explode('-',$company->permission))){
                $permission = explode('-', $company->permission);
                if(($key = array_search($permit_id, $permission)) !== false){
                    unset($permission[$key]);
                    $company = User::find($id);
                    $company->permission = implode('-', $permission);
                }
            }else{
                $company->permission = $company->permission.'-'.$permit_id;
            }
        }else {
            $company->permission = $permit_id;
        }
        $company->save();
    }
}
