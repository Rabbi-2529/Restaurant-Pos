<?php

namespace App\Jobs;

use App\Model\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendSMS implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    protected $phone;
    protected $order_no;
    protected $total_amount;
    protected $company_id;
    protected $api_key;
    protected $sender_id;

    public function __construct($phone, $order_no, $total_amount, $company_id, $api_key, $sender_id)
    {
        $this->phone = $phone;
        $this->order_no = $order_no;
        $this->total_amount = $total_amount;
        $this->company_id = $company_id;
        $this->api_key = $api_key;
        $this->sender_id = $sender_id;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $company = User::where(['company_id' => $this->company_id, 'role' => 2])->first();
        if($this->api_key != '' && $this->sender_id != '' || $company->api_key != '' && $company->sender_id != ''){
            if ($this->api_key != '' && $this->sender_id != '') {
                $api_key = $this->api_key;
                $sender_id = $this->sender_id;
            } else {
                if ($company->api_key != '' && $company->sender_id != '') {
                    $api_key = $company->api_key;
                    $sender_id = $company->sender_id;
                }
            }

            $mobile_number= $this->phone;
            $message = 'Your order no is: #00'.$this->order_no.'. Total Amount is: '.$this->total_amount.'Tk. Thank you.';
            $message = urlencode($message);
            // $api_key = "445156057064961560570649";
            $client = new \GuzzleHttp\Client();
            $api_url = "http://sms.iglweb.com/api/v1/send?api_key=". $api_key ."&contacts=". $mobile_number ."&senderid=". $sender_id ."&msg=".$message;
            $response = $client->request('GET', "$api_url");
            // dd($api_url);
            $json_response = $response->getBody()->getContents();
            $api_response = json_decode($json_response);
        }
    }
}
