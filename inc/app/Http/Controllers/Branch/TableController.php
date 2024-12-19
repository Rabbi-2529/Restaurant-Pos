<?php

namespace App\Http\Controllers\Branch;

use Auth;
use Carbon\Carbon;
use App\Model\Table;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TableController extends Controller
{   
    public function createTable(Request $request){
        $checkTable = Table::where(['company_id' => Auth::user()->company_id, 'branch_id' => Auth::user()->branch_id, 'table_no' => $request->table_no])->count();
        if($checkTable > 0){
            return response()->json([], 400);
        }
        $table = new Table();
        $table->create_by = Auth::user()->id;
        $table->company_id = Auth::user()->company_id;
        $table->branch_id = Auth::user()->branch_id;
        $table->table_no = $request->table_no;
        $table->save();
    }


    public function allTable(){
        $tables = Table::where(['company_id' => Auth::user()->company_id, 'branch_id' => Auth::user()->branch_id])->get();
        return $tables;
    }

    public function editTable($id){
        $table = Table::find($id);
        return $table;
    }

    public function updateTable(Request $request, $id){
        $checkTable = Table::where('id', '!=', $id)->where(['company_id' => Auth::user()->company_id, 'branch_id' => Auth::user()->branch_id, 'table_no' => $request->table_no])->count();
        if($checkTable > 0){
            return response()->json([], 400);
        }
        $table = Table::find($id);;
        $table->table_no = $request->table_no;
        $table->save();
    }

    public function deleteTable($id){
        $table = Table::find($id);
        $table->delete();
    }
}
