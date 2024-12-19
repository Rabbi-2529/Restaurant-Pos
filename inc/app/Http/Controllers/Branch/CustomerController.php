<?php

namespace App\Http\Controllers\Branch;

use Auth;
use Carbon\Carbon;
use App\Model\Customer;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Exports\CustomerExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Validator;

class CustomerController extends Controller
{
    public function allCustomer(){
        $customers = Customer::where(['company_id' => Auth::user()->company_id, 'branch_id' => Auth::user()->branch_id])->get();
        return $customers;
    }

    public function export(){
        return Excel::download(new CustomerExport, 'customers.xlsx');
    }

}
