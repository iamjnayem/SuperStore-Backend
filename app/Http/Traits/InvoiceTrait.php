<?php
namespace App\Http\Traits;

use App\Models\User;
use Illuminate\Support\Facades\Log;

trait InvoiceTrait
{
    public $statuses = [
        0 => 'Draft',
        1 => 'Pending',
        2 => 'Due',
        3 => 'Rejected',
        4 => 'Expired',
        7 => 'Scheduled',
        9 => 'Paid',
        10 =>'Refund',
    ];

    public function getAllStatues()
    {
        if (auth()->user()->user_type == 'merchant'){
            return [
                0 => 'Draft',
                1 => 'Pending',
                2 => 'Receivable',
                3 => 'Declined',
                4 => 'Expired',
                7 => 'Scheduled',
                9 => 'Received',
                10 =>'Refund',
            ];
        }else{
            return[
                1 => 'Pending',
                2 => 'Payable',
                3 => 'Rejected',
                4 => 'Over Due',
                7 => 'Scheduled',
                9 => 'Paid',
                10 =>'Refund',
            ];
        }
    }

    public function getStatus($code)
    {
        return isset($this->statuses[$code]) ? $this->statuses[$code] : $code;
    }

    public function getStatusAliasName($code,$requestType=null)
    {
        if ($requestType == 'merchant'){
            return $this->merchantAliasStatus($code);
        }
        else{
            return $this->consumerAliasStatus($code);
        }
    }

    public function merchantAliasStatus($code)
    {
        return match ($code) {
            2 => 'Receivable',
            3 => 'Declined',
            9 => 'Received',
            default => $this->getStatus($code),
        };
    }

    public function consumerAliasStatus($code)
    {
        return match ($code) {
            2 => 'Payable',
            4 => 'Over Due',
            default => $this->getStatus($code),
        };
    }

    function make_star($request)
    {
        $this->request = $request;
    }

    function remove_star($request)
    {
        $this->request = $request;
    }
}
