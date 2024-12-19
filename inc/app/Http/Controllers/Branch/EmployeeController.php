<?php

namespace App\Http\Controllers\Branch;

use File;
use Auth;
use Carbon\Carbon;
use App\Model\User;
use App\Model\UserDetail;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class EmployeeController extends Controller
{
    public function checkEmail(Request $request){
        // dd($request->email);
        $countUser = User::where(['email'=> $request->email])->count();
        if($countUser > 0){
            return response()->json([
                'status' => 'exist'
            ]);
        }
    }


    public function checkPhone(Request $request){
        // dd($request->phone);
        $countUser = User::where(['phone'=> $request->phone])->count();
        if($countUser > 0){
            return response()->json([
                'status' => 'exist'
            ]);
        }
    }


    public function checkEmailById(Request $request, $id){
        // dd($request->email);
        $countUser = User::where('id', '!=', $id)->where(['email'=> $request->email])->count();
        if($countUser > 0){
            return response()->json([
                'status' => 'exist'
            ]);
        }
    }

    public function checkPhoneById(Request $request, $id){
        // dd($request->phone);
        $countUser = User::where('id', '!=', $id)->where(['phone'=> $request->phone])->count();
        if($countUser > 0){
            return response()->json([
                'status' => 'exist'
            ]);
        }
    }


    public function createEmp(Request $request){
        $checkEmail = User::where(['email'=> $request->email])->count();
        $checkPhone = User::where(['phone'=> $request->phone])->count();
        if($checkEmail > 0){
            return response()->json([], 400);
        }elseif($checkPhone > 0){
            return response()->json([], 401);
        }else{
            $employee = new User();
            $employee->company_id = Auth::user()->company_id;
            $employee->company_name = Auth::user()->company_name;
            $employee->branch_id = Auth::user()->branch_id;
            $employee->create_by = Auth::user()->id;
            $employee->branch_name = Auth::user()->branch_name;
            $employee->name = $request->name;
            $employee->email = $request->email;
            $employee->phone = $request->phone;
            $employee->password = Hash::make($request->password);
            $employee->status = '1';
            $employee->role = '4';
            $employee->save();

            $employeeDetail = new UserDetail();
            $employeeDetail->user_id =  $employee->id;
            $employeeDetail->name = $request->name;
            $employeeDetail->designation = $request->designation;
            $employeeDetail->str_pass = $request->password;
            $employeeDetail->address = $request->address;
            $employeeDetail->image = $request->image;
            $employeeDetail->save();
        }
    }


    public function allEmp(){
        $employees = User::where(['company_id' => Auth::user()->company_id, 'branch_id' => Auth::user()->branch_id, 'role' => '4'])->orderBy('name', 'asc')->get();
    	return $employees;
    }


    public function editEmp($id){
        $employee = User::find($id);
        $employeeDetail = UserDetail::where('user_id', $id)->first();
        return response()->json([
            'employee' => $employee,
            'employeeDetail' => $employeeDetail
        ]);
    }


    public function updateEmp(Request $request, $id){
        $employee = User::find($id);
        $employee->name = $request->name;
        $employee->email = $request->email;
        $employee->phone = $request->phone;
        if(!empty($request->password)){
            $employee->password = Hash::make($request->password);
        }
        $employee->save();

        $employeeDetail = UserDetail::where('user_id', $id)->first();
        $employeeDetail->name = $request->name;
        $employeeDetail->designation = $request->designation;
        if(!empty($request->password)){
            $employeeDetail->str_pass = $request->password;
        }
        $employeeDetail->address = $request->address;
        /*if has image save it*/
        if ($request->image != '') {
            if(File::exists('assets/uploads/user_logo/'.$employeeDetail->image)){
                File::delete('assets/uploads/user_logo/'.$employeeDetail->image);
            }
            $employeeDetail->image = $request->image;
        }
        $employeeDetail->save();

    }


    public function changeEmpStatus($id){
        $employee = User::find($id);
        // 1 = active, 2 = suspend
        if($employee->status == '1'){
            $employee->status = '2';
        }else{
            $employee->status = '1';
        }
        $employee->save();
    }


    public function deleteEmp($id){
        $employee = User::find($id);
        $employeeDetail = UserDetail::where('user_id', $id)->first();
        if(File::exists('assets/uploads/user_logo/'.$employeeDetail->image)){
            File::delete('assets/uploads/user_logo/'.$employeeDetail->image);
        }
        $employee->delete();
    }

    public function uploadEmpImage(Request $request){
        $filename = time().'.'.$request->file->extension();
        $request->file->move('assets/uploads/user_logo', $filename);
        return $filename;
    }

    public function deleteEmpImage(Request $request){
        if(File::exists('assets/uploads/user_logo/'.$request->image)){
            File::delete('assets/uploads/user_logo/'.$request->image);
        }
    }

    public function deleteEmpImageFromServer(Request $request, $id){
        if(File::exists('assets/uploads/user_logo/'.$request->image)){
            File::delete('assets/uploads/user_logo/'.$request->image);
        }
        $employeeDetail = UserDetail::where('user_id', $id)->first();
        $employeeDetail->image = null;
        $employeeDetail->save();
    }

    public function updateEmpPermission(Request $request, $id){
        $employee = User::find($id);
        $permit_id = $request->permit_id;

        if (!empty($employee->permission)) {
            if(in_array($permit_id, explode('-',$employee->permission))){
                $permission = explode('-', $employee->permission);
                if(($key = array_search($permit_id, $permission)) !== false){
                    unset($permission[$key]);
                    $employee = User::find($id);
                    $employee->permission = implode('-', $permission);
                }
            }else{
                $employee->permission = $employee->permission.'-'.$permit_id;
            }
        }else {
            $employee->permission = $permit_id;
        }
        $employee->save();
    }

}
