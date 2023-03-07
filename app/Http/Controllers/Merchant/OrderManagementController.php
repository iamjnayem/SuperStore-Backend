<?php

namespace App\Http\Controllers\Merchant;

use App\Constants\OrderStatus;
use App\Constants\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\OrderDetailsResource;
use App\Http\Resources\OrderListResource;
use App\Http\Traits\RequestTrait;
use App\Http\Traits\UpdateShopStockTrait;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class OrderManagementController extends Controller
{
    use RequestTrait, UpdateShopStockTrait;

    public function index(): JsonResponse
    {
        try {
            $orders =Order::with('merchant','users','items','order_item_details')
                ->where('merchant_id',auth()->user()->id)
                ->when(request()->query('search'), function ($q) {
                    $q->search();
                })
                ->orderBy('id','desc')
                ->get();

            //return $orders;

            return $this->SuccessResponse(OrderListResource::collection($orders),['Success'],200);

        }catch (ValidationException $exception){
            $this->configureException($exception);
        }catch (\Exception $exception){
            $this->writeErrors($exception);
        }
        return $this->FailResponse($this->message['data'],$this->message['messages'],$this->message['status'],$this->message['custom_code'],$this->message['validation']);


    }

    public function details($id): JsonResponse
    {
        try {
            $orders =Order::with('merchant','users','items','order_item_details')
                ->where('merchant_id',auth()->user()->id)
                ->where('id',$id)
                ->first();

            if ($orders){
                return $this->SuccessResponse(new OrderDetailsResource($orders),['Success'],200);
            }
            return $this->FailResponse([],['Invalid order id.'],422);


        }catch (ValidationException $exception){
            $this->configureException($exception);
        }catch (\Exception $exception){
            $this->writeErrors($exception);
        }
        return $this->FailResponse($this->message['data'],$this->message['messages'],$this->message['status'],$this->message['custom_code'],$this->message['validation']);


    }


    /*
     * Order status update
     *
     * */
    public function statusUpdate(Request $request): JsonResponse
    {
        try{

            DB::beginTransaction();
            $request->validate([
                'order_id' => 'required|exists:orders,id',
                'status' => ['required',Rule::in([
                    OrderStatus::WAITING_FOR_ACCEPT,
                    OrderStatus::ACCEPTED,
                    OrderStatus::READY_FOR_PICK_UP,
                    OrderStatus::CANCEL,
                    OrderStatus::REFUND
                ])]
            ]);

            $checkOrder = Order::where('merchant_id',auth()->user()->id)
                ->where('id',$request->order_id)
                ->where('status','=',OrderStatus::CANCEL)
                ->where('updated_by','!=',auth()->user()->id)
                ->first();

            if ($checkOrder){
                return $this->SuccessResponse(['messages' => 'The order has already been cancelled.'],['Success'],200);
            }

            if ($request->status == OrderStatus::READY_FOR_PICK_UP){
                $order = Order::where('merchant_id',auth()->user()->id)
                    ->where('id',$request->order_id)
                    ->where('status','!=',OrderStatus::DELIVERED)
                    ->update([
                        'status' => $request->status,
                        'payment_status' => PaymentStatus::PROCESSING,
                        'otp' => random_int(100000, 999999),
                        'updated_by' =>auth()->user()->id
                    ]);
            }
            elseif ($request->status == OrderStatus::CANCEL){
                $order = Order::where('merchant_id',auth()->user()->id)
                    ->where('id',$request->order_id)
                    ->where('status','!=',OrderStatus::DELIVERED)
                    ->update([
                        'status' => $request->status,
                        'payment_status' => PaymentStatus::DECLINED,
                        'updated_by' =>auth()->user()->id
                    ]);
                $this->updateStock($request->order_id);
            }
            elseif ($request->status == OrderStatus::REFUND){
                $order = Order::where('merchant_id',auth()->user()->id)
                    ->where('id',$request->order_id)
                    ->where('status','=',OrderStatus::DELIVERED)
                    ->update([
                        'status' => $request->status,
                        'payment_status' => PaymentStatus::REFUND,
                        'updated_by' =>auth()->user()->id
                    ]);
                $this->updateStock($request->order_id);
            }
            else{
                $order = Order::where('merchant_id',auth()->user()->id)
                    ->where('id',$request->order_id)
                    ->where('status','!=',OrderStatus::DELIVERED)
                    ->update([
                        'status' => $request->status,
                        'payment_status' => PaymentStatus::PROCESSING,
                        'updated_by' =>auth()->user()->id
                    ]);
            }

            if ($order){
                DB::commit();

                return $this->SuccessResponse(['messages' => 'Order status update successfully.'],['Success'],200);
            }

            return $this->FailResponse([],['Invalid Order item'],422);

        }catch (ValidationException $exception){
            DB::rollBack();
            $this->configureException($exception);
        }catch (\Exception $exception){
            $this->writeErrors($exception);
            DB::rollBack();
        }
        return $this->FailResponse($this->message['data'],$this->message['messages'],$this->message['status'],$this->message['custom_code'],$this->message['validation']);

    }


    /*
     * verify otp and delivered the order
     *
     * */
    public function verifyOtpForDelivery(Request $request): JsonResponse
    {

        try{

            $request->validate([
                'order_id' => 'required|exists:orders,id',
                'otp' => 'required',
            ]);


            $order = Order::where('merchant_id',auth()->user()->id)
                ->where('id',$request->order_id)
                ->first();

            if ($order && ($order->status == OrderStatus::ALIAS_DELIVERED)){
                return $this->FailResponse([],['Order already delivered.'],422);
            }

            if ($order && ($order->otp == $request->otp)){
                $order->update([
                   'status' => OrderStatus::DELIVERED,
                    'payment_status' => PaymentStatus::PAID,
                    'payment_date' => Carbon::now()->format('Y-m-d H:i:s'),
                    'otp' => null
                ]);
                //return $this->SuccessResponse(['messages' => 'Order delivered.'],$order,200);
                return $this->SuccessResponse($order,[OrderStatus::STATUS_DELIVERED],200);
            }

            return $this->FailResponse([],['Invalid otp.'],422);

        }catch (ValidationException $exception){
            $this->configureException($exception);
        }catch (\Exception $exception){
            $this->writeErrors($exception);
        }
        return $this->FailResponse($this->message['data'],$this->message['messages'],$this->message['status'],$this->message['custom_code'],$this->message['validation']);

    }

}
