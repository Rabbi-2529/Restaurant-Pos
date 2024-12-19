<?php

namespace App\Http\Controllers\Branch\Accounts;

use Carbon\Carbon;
use App\Model\Bank;
use App\Model\Inv_acc_bank_statement;
use App\Model\Card;
use App\Model\MobileBank;
use App\Model\Supplier;
use App\Model\BankInfo;
use App\Model\Expense;
use App\Model\ExpenseCategory;
use App\Model\Ledger;
use App\Model\LedgerCategory;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AccountController extends Controller
{
    public function allBank(){
        $banks = Bank::all();
    	return $banks;
    }


    public function addBankInfo(Request $request){
        $bank = new BankInfo();
        $bank->company_id = Auth::user()->company_id;
        $bank->branch_id = Auth::user()->branch_id;
        $bank->bank_id = $request->bank_id;
        $bank->submit_by = Auth::user()->id;
        $bank->branch_name = $request->branch_name;
        $bank->acc_name = ucfirst($request->acc_name);
        $bank->acc_no = $request->acc_no;
        $date = Carbon::parse($request->opendate);
        $opendate = $date->format('Y-m-d');
        $bank->opendate = $opendate;
        $bank->acc_type = 1; //1=bank
        $bank->save();
    }


    public function allBankInfo(){
        $bankInfos = BankInfo::with('bank')->where(['company_id' => Auth::user()->company_id, 'branch_id' => Auth::user()->branch_id, 'status' => 1])->orderBy('id', 'desc')->get();
    	return $bankInfos;
    }

    public function addCard(Request $request){
        $card = new Card();
        $card->company_id = Auth::user()->company_id;
        $card->branch_id = Auth::user()->branch_id;
        $card->bank_id = $request->bank_id;
        $card->submit_by = Auth::user()->id;
        $card->card_name = $request->card_name;
        $card->save();
    }

    public function allCardInfo(){
        $cardInfos = Card::with('bank')->where(['company_id' => Auth::user()->company_id, 'branch_id' => Auth::user()->branch_id])->orderBy('id', 'desc')->get();
    	return $cardInfos;
    }


    public function addMbank(Request $request){
        $card = new MobileBank();
        $card->company_id = Auth::user()->company_id;
        $card->branch_id = Auth::user()->branch_id;
        $card->submit_by = Auth::user()->id;
        $card->bank_name = $request->bank_name;
        $card->vat = $request->vat;
        $card->save();
    }


    public function allMbankInfo(){
        $mbankInfos = MobileBank::where(['company_id' => Auth::user()->company_id, 'branch_id' => Auth::user()->branch_id])->orderBy('bank_name', 'asc')->get();
    	return $mbankInfos;
    }

    public function addSupplier(Request $request){
        $supplier = new Supplier();
        $supplier->company_id = Auth::user()->company_id;
        $supplier->branch_id = Auth::user()->branch_id;
        $supplier->company_name = $request->company_name;
        $supplier->owner_name = $request->owner_name;
        $supplier->phone = $request->phone;
        $supplier->address = $request->address;
        $supplier->type = $request->type;
        $supplier->save();
    }


    public function allSupplier(){
        $suppliers = Supplier::where(['company_id' => Auth::user()->company_id, 'branch_id' => Auth::user()->branch_id])->orderBy('id', 'desc')->get();
    	return $suppliers;
    }

    public function addExpenseCategory(Request $request){
        $expenseCat = new ExpenseCategory();
        $expenseCat->company_id = Auth::user()->company_id;
        $expenseCat->branch_id = Auth::user()->branch_id;
        $expenseCat->category_name = $request->category_name;
        $expenseCat->save();
    }


    public function allExpenseCategory(){
        $expenseCategories = ExpenseCategory::with('expenses')->where(['company_id' => Auth::user()->company_id, 'branch_id' => Auth::user()->branch_id])->orderBy('id', 'desc')->get();
    	return $expenseCategories;
    }

    public function addExpense(Request $request){
        $expense = new Expense();
        $expense->company_id = Auth::user()->company_id;
        $expense->branch_id = Auth::user()->branch_id;
        $expense->category_id = $request->category_id;
        $expense->expense_name = $request->expense_name;
        $expense->save();
    }

    public function allExpense(){
        $expenses = Expense::with('category')->where(['company_id' => Auth::user()->company_id, 'branch_id' => Auth::user()->branch_id])->orderBy('id', 'desc')->get();
    	return $expenses;
    }

    public function addLedgerCategory(Request $request){
        $expenseCat = new LedgerCategory();
        $expenseCat->company_id = Auth::user()->company_id;
        $expenseCat->branch_id = Auth::user()->branch_id;
        $expenseCat->category_name = $request->category_name;
        $expenseCat->save();
    }


    public function allLedgerCategory(){
        $expenseCategories = LedgerCategory::with('ledgers')->where(['company_id' => Auth::user()->company_id, 'branch_id' => Auth::user()->branch_id])->orderBy('id', 'desc')->get();
    	return $expenseCategories;
    }

    public function addLedger(Request $request){
        $expense = new Ledger();
        $expense->company_id = Auth::user()->company_id;
        $expense->branch_id = Auth::user()->branch_id;
        $expense->category_id = $request->category_id;
        $expense->expense_name = $request->expense_name;
        $expense->save();
    }

    public function allLedger(){
        $expenses = Ledger::with('category')->where(['company_id' => Auth::user()->company_id, 'branch_id' => Auth::user()->branch_id])->orderBy('id', 'desc')->get();
    	return $expenses;
    }


    public function insert_ledger_data(Request $request){
        // dd($request->all());
        $com = Auth::user()->company_id;
        $branch = Auth::user()->branch_id;
        $time = Carbon::now()->format('Y-m-d H:i:s');
        $user = Auth::user()->id;
        $issue_date = $request->issue_date;
        $bank_id = BankInfo::where('company_id',$com)
                                    ->where('acc_type',1)
                                    ->where('status',1)
                                    ->first();
        if (isset($request->income)) {
            // for ($i=0; $i <count($request->income); $i++) {
                $ledger_add = new Inv_acc_bank_statement();
                $ledger_add->inv_abs_company_id = $com;
                // $ledger_add->inv_abs_inventory_id = $ledge_inventory->inv_pro_inv_id;
                // $ledger_add->inv_abs_reference_id = $request->customer[$i];
                $ledger_add->inv_abs_reference_type = 8; //income-add
                $ledger_add->inv_abs_bank_id = $bank_id->id;
                $ledger_add->inv_abs_debit = 0;
                $ledger_add->inv_abs_credit = $request->cash_in;
                $ledger_add->inv_abs_transaction_date = $issue_date;
                // $ledger_add->inv_abs_voucher_no = $new_memo_no;
                $ledger_add->inv_abs_description = $request->first_narration;
                $ledger_add->inv_abs_status = 1;
                $ledger_add->inv_abs_submit_by = $user;
                $ledger_add->inv_abs_submit_at = $time;
                $ledger_add->save();
            // }
        }

        if (isset($request->expense) && (count($request->expense)) > 0) {
            // dd($request->expense);
            for ($i=0; $i <count($request->expense); $i++) {
                // dd($request->expense[$i]['supplier']);
                $ledger_add = new Inv_acc_bank_statement();
                $ledger_add->inv_abs_company_id = $com;
                // $ledger_add->inv_abs_inventory_id = $ledge_inventory->inv_pro_inv_id;
                $ledger_add->inv_abs_reference_id = $request->expense[$i]['supplier'];
                $ledger_add->inv_abs_reference_type = 9; //expense-add
                $ledger_add->inv_abs_bank_id = $bank_id->id;
                $ledger_add->inv_abs_debit = $request->expense[$i]['cash_out'];
                $ledger_add->inv_abs_credit = 0;
                $ledger_add->inv_abs_transaction_date = $issue_date;
                // $ledger_add->inv_abs_voucher_no = $new_memo_no;
                // $ledger_add->inv_abs_description = $request->expense[$i]['second_narration'];
                $ledger_add->inv_abs_status = 1;
                $ledger_add->inv_abs_submit_by = $user;
                $ledger_add->inv_abs_submit_at = $time;
                $ledger_add->save();
            }
        }

    }
}
