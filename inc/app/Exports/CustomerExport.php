<?php

namespace App\Exports;

use App\Model\Customer;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CustomerExport implements FromCollection, WithHeadings
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return Customer::select('cust_id','name', 'phone')->get();
    }

    public function headings(): array
    {
        return [
            'Customer ID',
            'Customer Name',
            'Customer Phone'
        ];
    }
}
