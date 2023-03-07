<?php

namespace App\Http\Controllers\Personal;

use App\Constants\OrderStatus;
use App\Constants\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\OrderRequest;
use App\Http\Requests\OrderSummaryRequest;
use App\Http\Resources\OrderDetailsResource;
use App\Http\Resources\OrderListResource;
use App\Http\Traits\RequestTrait;
use App\Http\Traits\ShopTimeValidationTrait;
use App\Http\Traits\UpdateShopStockTrait;
use App\Models\Item;
use App\Models\Order;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    use RequestTrait,UpdateShopStockTrait,ShopTimeValidationTrait;

    private $orderService;

    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }

    /*
     * get all orders
     *
     * */
    public function index(): JsonResponse
    {
        $orders = $this->orderService->getOrderList();
        return $this->SuccessResponse(OrderListResource::collection($orders)->response()->getData(true), 'Success', 200);
        //return $this->SuccessResponse($orders,'Success',200);
    }
    /*
     * get all orders
     *
     * */
    public function details($id): JsonResponse
    {
        $orders = $this->orderService->getOrderDetails($id);

        if ($orders) {
            return $this->SuccessResponse(new OrderDetailsResource($orders), 'Success', 200);
        }
        return $this->FailResponse([], ['Invalid order id.'], 422);

        //return $this->SuccessResponse($orders,'Success',200);
    }

    /*
     * Store requested order
     *
     * */
    public function store(OrderRequest $request): JsonResponse
    {
        $data = $request->validated();
        $merchantId = Item::where('user_id', $data['merchant_id'])->value('user_id');

        $validTime = $this->isValidTime($data['merchant_id']);

        if (!$validTime){
            return $this->FailResponse([], ['Shop is closed for now.'], 422);
        }

        if ($this->orderService->isAmountValid($data, $merchantId)) {
            $order = $this->orderService->saveNewOrder($data, $merchantId);
            $this->orderService->saveNetSale($order);
            return $this->SuccessResponse($order, 'Success', 200);
        }

        return $this->FailResponse([], ['Total amount and payable amount mismatch.'], 422);
    }

    /*
     * Order Summary and check requested order Amount
     *
     * */
    public function summary(OrderSummaryRequest $request): JsonResponse
    {
        $data = $request->validated();

        $validTime = $this->isValidTime($data['merchant_id']);

        if (!$validTime){
            return $this->FailResponse([], ['Shop is closed for now.'], 422);
        }

        $merchantId = Item::where('user_id', $data['merchant_id'])->value('user_id');
        $order = $this->orderService->validateOrder($data, $merchantId);

        unset($order['invoice_id']);

        $order['payable'] = $request['payable'];


        $order['receiver_mobile_no'] = User::where('id', $merchantId)->first()->mobile_no;

        if ($this->orderService->isAmountValid($data, $merchantId)) {
            return $this->SuccessResponse($order, 'Success', 200);
        }

        return $this->FailResponse([], ['Total amount and payable amount mismatch.'], 422);
    }

    /*
     * Cancel Order
     *
     * */
    public function cancelOrder($id): JsonResponse
    {
        try {

            DB::beginTransaction();

            $order = Order::where('user_id', auth()->user()->id)
                ->where('id', $id)
                ->where('status', OrderStatus::WAITING_FOR_ACCEPT)
                ->update([
                    'status' => OrderStatus::CANCEL,
                    'payment_status' => PaymentStatus::DECLINED,
                    'updated_by' =>auth()->user()->id
                ]);

            $updateStock = $this->updateStock($id);

            if ($order & $updateStock) {
                DB::commit();
                return $this->SuccessResponse(['messages' => 'Order update successfully.'], ['Success'], 200);
            }

            return $this->FailResponse([], ['Invalid order selected.'], 422);

        } catch (ValidationException $exception) {
            DB::rollBack();
            $this->configureException($exception);
        } catch (\Exception $exception) {
            $this->writeErrors($exception);
            DB::rollBack();
        }
        return $this->FailResponse($this->message['data'], $this->message['messages'], $this->message['status'], $this->message['custom_code'], $this->message['validation']);
    }



}
