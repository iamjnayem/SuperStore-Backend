<?php

namespace App\Http\Resources;

use App\Constants\OrderStatus;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Log;

class OrderListResource extends JsonResource
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
            'user_photo' => $this->users->photo,
            'merchant_id' => $this->merchant_id,
            'merchant_name' => $this->merchant->name,
            'merchant_mobile_no' => $this->merchant->mobile_no,
            'merchant_photo' => $this->merchant->avatar,
            'invoice_id' => $this->invoice_id,
            'transaction_id' => $this->transaction_id,
            'order_type' => $this->order_type ,
            'order_type_alias' => $this->order_type == 1 ? OrderStatus::ORDER_TYPE_PICKUP : OrderStatus::ORDER_TYPE_DINEIN ,
            'total_price' => number_format($this->total_price,2,'.'),
            'payment_status' => $this->payment_status,
            'status' => $this->status,
            'status_alias' => $this->getStatusAlias($this->status),
            'items_count' => count(OrderItemResource::collection($this->items)),
            'created_at_date' => dateMonthYearFormat($this->created_at),
            'created_at_time' => timeFormat($this->created_at),
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
