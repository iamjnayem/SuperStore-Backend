<?php

namespace App\Http\Controllers;

use App\Http\Traits\RequestTrait;
use App\Models\Category;
use App\Models\Invoice;
use App\Models\Item;
use App\Models\Order;
use App\Constants\OrderStatus;
use App\Models\User;
use Illuminate\Foundation\Bootstrap\LoadConfiguration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class DashboardController extends Controller
{
    use RequestTrait;

    public function __construct(Request $request)
    {
        $this->setSettings($request);
    }

    public function receivable()
    {
        try {

            $amountReceived = Invoice::where(['merchant_user_id'=>auth()->user()->id, 'status'=>9])->sum('final_bill_amount');
            $amountReceivable = Invoice::where(['merchant_user_id'=>auth()->user()->id, 'status'=>2])->sum('final_bill_amount');


            $data = [
                'total_received' => $amountReceived,
                'total_receivable' => $amountReceivable,
            ];

            return $this->SuccessResponse($data);

        }catch (ValidationException $exception){
            $this->configureException($exception);
        }catch (\Exception $exception){
            $this->writeErrors($exception);
        }
        return $this->FailResponse($this->message['data'],$this->message['messages'],$this->message['status'],$this->message['custom_code'],$this->message['validation']);
    }

    public function payable()
    {
        try {
            $amountPaid = Invoice::where(['user_id'=>auth()->user()->id, 'status'=>9])->sum('final_bill_amount');
            $amountPayable = Invoice::where(['user_id'=>auth()->user()->id, 'status'=>2])->sum('final_bill_amount');

            $data = [
                'total_paid' => $amountPaid,
                'total_payable' => $amountPayable,
            ];
            return $this->SuccessResponse($data);

        }catch (ValidationException $exception){
            $this->configureException($exception);
        }catch (\Exception $exception){
            $this->writeErrors($exception);
        }
        return $this->FailResponse($this->message['data'],$this->message['messages'],$this->message['status'],$this->message['custom_code'],$this->message['validation']);
    }

    public function my_items()
    {
        try {
            $items = Item::where(['user_id'=>auth()->user()->id])->count();
            $categories = Category::where(['user_id'=>auth()->user()->id])->count();
            $data = [
                'total_items' => $items,
                'categories' => $categories,
            ];
            return $this->SuccessResponse($data);

        }catch (ValidationException $exception){
            $this->configureException($exception);
        }catch (\Exception $exception){
            $this->writeErrors($exception);
        }
        return $this->FailResponse($this->message['data'],$this->message['messages'],$this->message['status'],$this->message['custom_code'],$this->message['validation']);
    }

    public function total_sales()
    {
        try {
            $data = [
                'total_sales' => "0",
                'items' => [],
            ];
            return $this->SuccessResponse($data);

        }catch (ValidationException $exception){
            $this->configureException($exception);
        }catch (\Exception $exception){
            $this->writeErrors($exception);
        }
        return $this->FailResponse($this->message['data'],$this->message['messages'],$this->message['status'],$this->message['custom_code'],$this->message['validation']);
    }

    public function orderSummary()
    {
        try {
            $data = [
                'accepted_orders' => 0,
                'canceled_orders' => 0
            ];

            $orders = Order::where('merchant_id', auth()->user()->id)
                ->whereDay('created_at', now()->day)
                ->get();
            $orders = $orders->countBy('status');

            foreach ($orders as $key => $value) {
                switch ($key) {
                    case OrderStatus::ALIAS_ACCEPTED:
                        $data['accepted_orders'] = $value;
                        break;
                    case OrderStatus::ALIAS_CANCEL:
                        $data['canceled_orders'] = $value;
                        break;
                }
            }
            $data['total_orders'] = $data['accepted_orders'] + $data['canceled_orders'];
            return $this->SuccessResponse($data);
        } catch (ValidationException $exception) {
            $this->configureException($exception);
        } catch (\Exception $exception) {
            $this->writeErrors($exception);
        }
        return $this->FailResponse($this->message['data'], $this->message['messages'], $this->message['status'], $this->message['custom_code'], $this->message['validation']);
    }

}
