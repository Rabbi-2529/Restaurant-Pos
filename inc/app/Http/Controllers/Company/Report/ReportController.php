<?php

namespace App\Http\Controllers\Company\Report;

use Auth;
use Carbon\Carbon;
use App\Model\User;
use App\Model\UserDetail;
use App\Model\Order;
use App\Model\OrderDetail;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ReportController extends Controller
{
    public function todayReport(){
        $branches = User::where(['company_id' => Auth::user()->company_id, 'role' => 3])->get();
        // dd($branches);
        return $branches;
    }

    public function monthlyReport(){
        $branches = User::where(['company_id' => Auth::user()->company_id, 'role' => 3])->get();
        // dd($branches);
        return view('company.report.monthlyReport', compact('branches'));
    }
}