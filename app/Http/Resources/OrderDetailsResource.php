<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Constants\OrderStatus;


class OrderDetailsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'user_name' => $this->users->name,
            'user_mobile' => $this->users->mobile_no,
            'merchant_id' => $this->merchant_id,
            'shop_id' => $this->merchant->deshi_user_id,
            'merchant_name' => $this->merchant->name,
            'merchant_mobile_no' => $this->merchant->mobile_no,
            'merchant_photo' => $this->merchant->avatar,
            'invoice_id' => $this->invoice_id,
            'transaction_id' => $this->transaction_id,
            'order_type' => $this->order_type,
            'order_type_alias' => $this->order_type == 1 ? OrderStatus::ORDER_TYPE_PICKUP : OrderStatus::ORDER_TYPE_DINEIN ,
            'pickup_date' => $this->pickup_date ? dateToDateFormat($this->pickup_date) : null,
            'pickup_time' => $this->pickup_time,
            'total_item_price' => number_format($this->total_item_price,2,'.'),
            'discount_amount' => number_format($this->discount_amount,2,'.'),
            'total_vat' => number_format($this->total_vat,2,'.'),
            'service_fee' => number_format($this->service_fee,2,'.'),
            'total_price' => number_format($this->total_price,2,'.'),
            'paid_amount' => number_format($this->paid_amount,2,'.'),
            'refund_amount' => number_format($this->refund_amount,2,'.'),
            'payment_status' => $this->payment_status,
            'payment_date' => $this->payment_date,
            'user_note' => $this->user_note,
            'status' => $this->status,
            'status_alias' => $this->getStatusAlias($this->status),
            'items_count' => count(OrderItemResource::collection($this->items)),
            'items' => OrderItemResource::collection($this->items),
            'otp' => $this->otp,
            'created_at_date' => dateMonthYearFormat($this->created_at),
            'created_at_time' => timeFormat($this->created_at),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    public function getStatusAlias($value): string
    {
        return match ($value) {
            OrderStatus::ALIAS_READY_FOR_PICK_UP => OrderStatus::STATUS_READY_FOR_PICK_UP,
            OrderStatus::ALIAS_DELIVERED => OrderStatus::STATUS_DELIVERED,
            OrderStatus::ALIAS_CANCEL => OrderStatus::STATUS_CANCEL,
            OrderStatus::ALIAS_ACCEPTED => OrderStatus::STATUS_ACCEPTED,
            OrderStatus::ALIAS_REFUND => OrderStatus::STATUS_REFUND,
            default =>OrderStatus::STATUS_WAITING_FOR_ACCEPT,
        };
    }

}
