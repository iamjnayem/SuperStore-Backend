<?php

namespace App\Http\Controllers;

use App\Http\Traits\InvoiceTrait;
use App\Http\Traits\InvoiceTransactionTrait;
use App\Http\Traits\RequestTrait;
use App\Models\Invoice;
use App\Models\User;
use App\Models\Item;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class MerchantSalesReportController extends Controller
{
    use RequestTrait, InvoiceTrait, InvoiceTransactionTrait;

    public function __construct(Request $request)
    {
        $this->setSettings($request);
    }

    public function salesReport($id)
    {
        try {
            $user_id = User::where('deshi_user_id', $id)->value('id');

            $amountReceived = Invoice::when(request()->query('date'), function ($q) {
                $q->filterByDate();
            })
                ->where(['merchant_user_id' => $user_id, 'status' => 9])->sum('final_bill_amount');

            $amountReceivable = Invoice::when(request()->query('date'), function ($q) {
                $q->filterByDate();
            })
                ->where(['merchant_user_id' => $user_id, 'status' => 2])->sum('final_bill_amount');

            $amountPaid = Invoice::when(request()->query('date'), function ($q) {
                $q->filterByDate();
            })
                ->where(['user_id' => $user_id, 'status' => 9])->sum('final_bill_amount');

            $amountPayable = Invoice::when(request()->query('date'), function ($q) {
                $q->filterByDate();
            })
                ->where(['user_id' => $user_id, 'status' => 2])->sum('final_bill_amount');


            $data = [
                'total_received' => $amountReceived,
                'total_receivable' => $amountReceivable,
                'total_paid' => $amountPaid,
                'total_payable' => $amountPayable,
            ];

            return $this->SuccessResponse($data);
        } catch (ValidationException $exception) {
            $this->configureException($exception);
        } catch (\Exception $exception) {
            $this->writeErrors($exception);
        }
        return $this->FailResponse($this->message['data'], $this->message['messages'], $this->message['status'], $this->message['custom_code'], $this->message['validation']);
    }

    public function publishedItemSummary()
    {
        try {
            $netSale = DB::table('merchant_wise_published_net_sale')
                ->where('merchant_id', auth()->user()->id)
                ->value('net_sale_amount');

            $items = Item::where('user_id', auth()->user()->id)
                    ->where('status',1)
                    ->where('is_publish', 2)->count();

            $categories = Category::where('user_id', auth()->user()->id)
                ->where('status',1)    
                ->count();

            return $this->SuccessResponse(["net_sales_summary" => [
                'net_sale_amount' => $netSale ? $netSale : 0,
                'total_items' => $items,
                'total_categories' => $categories
            ]]);
        } catch (ValidationException $exception) {
            $this->configureException($exception);
        } catch (\Exception $exception) {
            $this->writeErrors($exception);
        }
        return $this->FailResponse($this->message['data'], $this->message['messages'], $this->message['status'], $this->message['custom_code'], $this->message['validation']);
    }
}
