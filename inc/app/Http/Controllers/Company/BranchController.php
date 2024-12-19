<?php

namespace App\Http\Controllers\Company;

use File;
use Auth;
use Carbon\Carbon;
use App\Model\User;
use App\Model\UserDetail;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BranchController extends Controller
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


    public function createBranch(Request $request){
        $checkEmail = User::where('email', $request->email)->count();
        $checkPhone = User::where('phone', $request->phone)->count();
        if($checkEmail > 0){
            return response()->json([], 400);
        }elseif($checkPhone > 0){
            return response()->json([], 401);
        }else{
            $comCount = User::where('role', '2')->count();
            if($comCount > 0){
                $pre_max_branch_id = User::where('branch_id', '!=', '')->max('branch_id');
                $new_branch_id = $pre_max_branch_id + 1;
            }else{
                $pre_max_branch_id = 26672690;
                $new_branch_id = $pre_max_branch_id + 1;
            }

            /*insert branch information*/
            $branch = new User();
            $branch->company_id = Auth::user()->company_id;
            $branch->company_name = Auth::user()->company_name;
            $branch->branch_id = $new_branch_id;
            $branch->create_by = Auth::user()->id;
            $branch->branch_name = $request->branch_name;
            $branch->name = $request->name;
            $branch->email = $request->email;
            $branch->phone = $request->phone;
            $branch->password = Hash::make($request->password);
            $branch->status = '1';
            $branch->role = '3';
            $branch->save();

            $branchDetail = new UserDetail();
            $branchDetail->user_id =  $branch->id;
            $branchDetail->name = $request->name;
            $branchDetail->designation = $request->designation;
            $branchDetail->str_pass = $request->password;
            $branchDetail->address = $request->address;
            $branchDetail->image = $request->image;
            $branchDetail->save();
        }
    }


    public function allBranch(){
        $branches = User::where(['company_id' => Auth::user()->company_id, 'role' => '3'])->orderBy('branch_name', 'asc')->get();
    	return $branches;
    }


    public function editBranch($id){
        $branch = User::find($id);
        $branchDetail = UserDetail::where('user_id', $id)->first();
        return response()->json([
            'branch' => $branch,
            'branchDetail' => $branchDetail
        ]);
    }


    public function updateBranch(Request $request, $id){
        $branch = User::find($id);
        $branch->branch_name = $request->branch_name;
        $branch->name = $request->name;
        $branch->email = $request->email;
        $branch->phone = $request->phone;
        if(!empty($request->password)){
            $branch->password = Hash::make($request->password);
        }
        $branch->save();

        $branchDetail = UserDetail::where('user_id', $id)->first();
        $branchDetail->name = $request->name;
        $branchDetail->designation = $request->designation;
        if(!empty($request->password)){
            $branchDetail->str_pass = $request->password;
        }
        $branchDetail->address = $request->address;
        /*if has image save it*/
        if ($request->image != '') {
            if(File::exists('assets/uploads/user_logo/'.$branchDetail->image)){
                File::delete('assets/uploads/user_logo/'.$branchDetail->image);
            }
            $branchDetail->image = $request->image;
        }
        $branchDetail->save();

    }


    public function changeBranchStatus($id){
        $branch = User::find($id);
        // 1 = active, 2 = suspend
        if($branch->status == '1'){
            $branch->status = '2';
        }else{
            $branch->status = '1';
        }
        $branch->save();
    }


    public function deleteBranch($id){
        $branch = User::find($id);
        $branchDetail = UserDetail::where('user_id', $id)->first();
        if(File::exists('assets/uploads/user_logo/'.$branchDetail->image)){
            File::delete('assets/uploads/user_logo/'.$branchDetail->image);
        }
        $branch->delete();
    }

    public function uploadBranchImage(Request $request){
        $filename = time().'.'.$request->file->extension();
        $request->file->move('assets/uploads/user_logo', $filename);
        return $filename;
    }

    public function deleteBranchImage(Request $request){
        if(File::exists('assets/uploads/user_logo/'.$request->image)){
            File::delete('assets/uploads/user_logo/'.$request->image);
        }
    }

    public function deleteBranchImageFromServer(Request $request, $id){
        if(File::exists('assets/uploads/user_logo/'.$request->image)){
            File::delete('assets/uploads/user_logo/'.$request->image);
        }
        $branchDetail = UserDetail::where('user_id', $id)->first();
        $branchDetail->image = null;
        $branchDetail->save();
    }

    public function updateBranchPermission(Request $request, $id){
        $branch = User::find($id);
        $permit_id = $request->permit_id;

        if (!empty($branch->permission)) {
            if(in_array($permit_id, explode('-',$branch->permission))){
                $permission = explode('-', $branch->permission);
                if(($key = array_search($permit_id, $permission)) !== false){
                    unset($permission[$key]);
                    $branch = User::find($id);
                    $branch->permission = implode('-', $permission);
                }
            }else{
                $branch->permission = $branch->permission.'-'.$permit_id;
            }
        }else {
            $branch->permission = $permit_id;
        }
        $branch->save();
    }


    public function showEmployee(Request $request){
        $employees = User::where(['company_id' => Auth::user()->company_id, 'branch_id' => $request->branch_id, 'role' => '4'])->orderBy('name', 'asc')->get();
    	return $employees;
    }


    public function transferBranches(Request $request){
        $tbranches = User::where('company_id', Auth::user()->company_id)->where('branch_id', '!=', $request->branch_id)->where('role', '3')->orderBy('branch_name', 'asc')->get();
    	return $tbranches;
    }


    public function transferEmployee(Request $request){
        $employee = User::where(['id' => $request->emp_id, 'company_id' => Auth::user()->company_id, 'branch_id' => $request->from_branch, 'role' => '4'])->first();
        $employee->branch_id = $request->to_branch;
        $employee->branch_name = User::branch_name($request->to_branch);
        $employee->save();
    }
}
