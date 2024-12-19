<?php

namespace App\Http\Controllers\Company;

use Auth;
use Carbon\Carbon;
use App\Model\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ApiController extends Controller
{   
    public function api(){
        $api = User::select(['api_key', 'sender_id'])->where(['company_id' => Auth::user()->company_id, 'role' => 2])->first();
        return $api;
    }

    public function updateApi(Request $request){
        $api = User::where(['company_id' => Auth::user()->company_id, 'role' => 2])->first();
        $api->api_key = $request->api_key;
        $api->sender_id = $request->sender_id;
        $api->save();
    }
}
