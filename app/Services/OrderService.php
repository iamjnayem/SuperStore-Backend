<?php

namespace App\Services;

use App\Constants\PaymentStatus;
use App\Exceptions\CustomException;
use App\Http\Controllers\Controller;
use App\Http\Traits\RequestTrait;
use App\Models\Discount;
use App\Models\Item;
use App\Models\Order;
use App\Models\ItemOrder;
use App\Models\MerchantWiseNetSale;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderService extends controller
{
    use RequestTrait;

    public function getOrderList()
    {
        return Order::with('merchant', 'users', 'items', 'order_item_details')
            ->where('user_id', auth()->user()->id)
            ->when(request()->query('search'), function ($q) {
                $q->search();
            })
            ->orderBy('id', 'desc')
            ->paginate(15);
    }

    public function getOrderDetails($id)
    {
        return Order::with('merchant', 'users', 'items', 'order_item_details')
            ->where('user_id', auth()->user()->id)
            ->where('id', $id)
            ->first();
    }

    public function saveNewOrder($request, $merchantId)
    {
        DB::beginTransaction();
        try {
            $verifiedOrder = $this->validateOrder($request, $merchantId);
            $verifiedOrder['transaction_id'] = $request['transaction_id'];
            $verifiedOrder['service_fee'] = $request['charge'] ?:0;
            $verifiedOrder['total_price'] = $verifiedOrder['total_price'] + $request['charge'];

            //Log::debug($verifiedOrder);

            //$receiver = User::where('id',$merchantId)->first()->mobile_no;

            $order = $this->storeOrderData($verifiedOrder);
            $this->saveOrderItems($request, $order, $merchantId);
            $this->updateStockQuantity($request);

            DB::commit();

            return $order;

        } catch (\Throwable $exception) {
            DB::rollBack();
            //Log::error('store-order-user_id-'. auth()->id(), ['error' => $exception->getMessage(), 'request' => $request]);
            throw new CustomException($exception);
        }
    }

    /**
     * @throws CustomException
     */
    public function validateOrder($request, $merchantId): array
    {
        $itemIds = [];
        $discountAmount = 0;
        $itemAddonPrice = 0;
        $itemPrice = 0;


        foreach ($request['items'] as $i => $item) {
            $itemIds[$i] = $item['id'];
        }

        $this->checkValidItem($itemIds, $merchantId);
        $this->checkItemStock($request, $merchantId);

        foreach ($request['items'] as $i => $item) {

            if (array_key_exists('addons', $item)) {
                $itemAddonPrice = $itemAddonPrice + ($this->getAddonsPrice($item['addons'])*$item['quantity']);
            }

            //$itemPrice = $itemPrice + $this->getItemPrice($item) + ($itemAddonPrice*$item['quantity']);
            $itemPrice = $itemPrice + $this->getItemPrice($item);

            $discountAmount = $discountAmount + $this->getDiscountAmount($item, $merchantId);

        }

        $totalPrice = ($itemPrice + $itemAddonPrice - $discountAmount);

        //dd($itemPrice,$itemAddonPrice,$discountAmount);


        return [
            'user_id' => auth()->user()->id,
            'merchant_id' => $request['merchant_id'],
            'invoice_id' => $request['invoice_id'],
            'order_type' => $request['order_type'],
            'pickup_date' => array_key_exists('pickup_date',$request) ? $request['pickup_date'] : null,
            'pickup_time' => array_key_exists('pickup_time',$request) ? $request['pickup_time'] : null,
            'total_item_price' => $itemPrice,
            'discount_amount' => $discountAmount,
            'total_price' => $totalPrice,
            'user_note' => $request['user_note'],
            'payment_status' => PaymentStatus::PROCESSING,
        ];
    }

    /*
     * Order amount and payable mismatch check
     *
     * */
    public function isAmountValid($request, $merchantId): bool
    {
        $order = $this->validateOrder($request, $merchantId);

        if ($request['payable'] == $order['total_price']) {
            return true;
        }

        return false;
    }

    public function storeOrderData($data)
    {
        return Order::create($data);
    }

    public function saveOrderItems($request, $order, $merchantId)
    {

        $data = [];

        foreach ($request['items'] as $i => $item) {

            $discountDetails = null;
            $addonsDetails = null;

            if (array_key_exists('discount_id', $item) && $item['discount_id']) {
                $discountDetails = json_encode([
                    'id' => $item['discount_id'],
                    'amount' => $this->getDiscountAmount($item, $merchantId),
                ]);

                $discount = Discount::where('id', $item['discount_id'])
                    ->where('merchant_id', $merchantId)
                    ->first();
                $discount->use_count = $discount->use_count + $item['quantity'];
                $discount->save();
            }

            if (array_key_exists('addons', $item)) {
                $addonsDetails = json_encode($item['addons']);
            }

            $data[$i]['order_id'] = $order->id;
            $data[$i]['item_id'] = $item['id'];
            $data[$i]['quantity'] = $item['quantity'];
            $data[$i]['addons'] = $addonsDetails;
            $data[$i]['discount_details'] = $discountDetails;
        }

        ItemOrder::insert($data);
    }

    /*
     * Update Item Stock quantity
     *
     * */
    public function updateStockQuantity($request)
    {

        foreach ($request['items'] as $i => $item) {

            $data[$i]['item_id'] = $item['id'];
            $data[$i]['quantity'] = $item['quantity'];

            $getItem = Item::where('id', $item['id'])->first();
            $getItem->stock_quantity = $getItem->stock_quantity - $item['quantity'];
            $getItem->save();
        }
    }

    public function checkValidItem($items, $merchantId): bool
    {

        try {
            Item::where('user_id', $merchantId)
                ->where('is_publish', 2)
                ->findOrFail($items);

            return true;
        } catch (\Exception $exception) {
            //$mData = explode(']',$exception->getMessage());
            //$message = 'Invalid selected item'.$mData[1];
            $message = 'Invalid selected item';
            throw new CustomException([$message], 422);
        }
    }

    /*
     * Check item stock is valid
     *
     * */
    public function checkItemStock($request, $merchantId): bool
    {
        try {

            foreach ($request['items'] as $item) {
                $getItem = Item::where('user_id', $merchantId)
                    ->where('id', $item['id'])
                    ->where('is_publish', 2)
                    ->first();
                if (!$getItem || $getItem->stock_quantity < $item['quantity']) {
                    $message = 'Item not available';
                    throw new CustomException([$message], 422, $message);
                }
            }

            return true;
        } catch (\Exception $exception) {
            //$mData = explode(']',$exception->getMessage());
            //$message = 'Invalid selected item'.$mData[1];
            $message = 'Item not available';
            throw new CustomException([$message], 422);
        }
    }

    public function getItemPrice($item): float|int
    {
        $itemPrice = Item::where('id', $item['id'])->value('unit_price');

        return ($itemPrice * $item['quantity']);
    }

    public function getAddonsPrice($addons)
    {
        $addonPrice = 0;
        foreach ($addons as $addon) {
            foreach ($addon['items'] as $item) {
                $addonPrice = $addonPrice + $item['unit_price'];
            }
        }
        return $addonPrice;
    }

    public function getDiscountAmount($item, $merchantId)
    {
        $discountAmount = 0;
        $getDiscountInfo = null;

        if (array_key_exists('discount_id', $item) && $item['discount_id']) {
            $getDiscountInfo = Discount::where('id', $item['discount_id'])
                ->where('merchant_id', $merchantId)
                ->where('end_date', '>', Carbon::now()->format('Y-m-d H:i:s'))
                //->whereJsonContains('items',$item['id'])
                ->first();
        }

        $itemPrice = $this->getItemPrice($item);

        if ($getDiscountInfo) {

            /*if (($getDiscountInfo->max_use - $getDiscountInfo->use_count) > 0) {

                if ($getDiscountInfo->is_percentage) {
                    $discountAmount = ($itemPrice * $getDiscountInfo->amount) / 100;
                } else {
                    $discountAmount = $getDiscountInfo->amount;
                }
            }*/

            if ($getDiscountInfo->is_percentage) {
                $discountAmount = ($itemPrice * $getDiscountInfo->amount) / 100;
            } else {
                $discountAmount = $getDiscountInfo->amount;
            }

        }



        return $discountAmount;
    }

    /*
    *  save net sale amount
    */
    public function saveNetSale($order)
    {
        $old_net_sale = DB::table('merchant_wise_published_net_sale')
            ->where('merchant_id', $order->merchant_id)
            ->first();

        MerchantWiseNetSale::updateOrCreate(
            [
                'merchant_id' => $order->merchant_id
            ],
            [

                'net_sale_amount' => ($old_net_sale ? $old_net_sale->net_sale_amount : 0) + $order->total_price
            ]
        );
    }
}
