<?php

namespace App\Http\Resources;

use App\Http\Traits\InvoiceTrait;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
{
    use InvoiceTrait;
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        //return parent::toArray($request);
        return [
            'id' => $this->id,
            'invoice_id' => $this->invoice_id,
            'transaction_id' => $this->transaction_id->deshi_transaction_id ?? '',
            'custom_invoice_id' => $this->invoice_id,
            'title' => $this->title,
            'total_bill_amount' => $this->total_bill_amount,
            'final_bill_amount' => $this->final_bill_amount,
            'discount_amount' => $this->discount_amount,
            'delivery_charge_amount' => $this->delivery_charge_amount,
            'vat_tax_amount' => $this->vat_tax_amount,
            'expire_after' => $this->expire_after,
            'reminder' => $this->reminder,
            'notes' => $this->notes,
            'is_starred' => $this->is_starred,
            'status' => $this->getStatus($this->status),
            'status_alias' => $this->getStatusAliasName($this->status,'merchant'),
            'head' => "Bill " . $this->getStatus($this->status),
            'schedule_at_date' => $this->schedule_at_date,
            'schedule_at_time' => $this->schedule_at_time,
            'created_at_date' => Carbon::createFromFormat('Y-m-d H:i:s', $this->created_at)->format('Y-m-d'),
            'created_at_time' => Carbon::createFromFormat('Y-m-d H:i:s', $this->created_at)->format('H:i:s'),
            'updated_at' => Carbon::createFromFormat('Y-m-d H:i:s', $this->updated_at)->format('Y-m-d H:i:s'),
            'due_date'=>$this->due_date,
            'notification_method' => explode(',',$this->notification_method),
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'avatar' => $this->user->avatar,
                'mobile_no' => $this->user->mobile_no,
                'user_type' => $this->user->user_type,
                'address' => $this->user->address,
                'email' => $this->user->email,
            ],
            'merchant' => [
                'id' => $this->merchant->id,
                'name' => $this->merchant->name,
                'avatar' => $this->merchant->avatar,
                'mobile_no' => $this->merchant->mobile_no,
                'user_type' => $this->merchant->user_type,
                'address' => $this->merchant->address,
                'email' => $this->merchant->email,
            ],
            'items' => $this->invoice_items->map(function($item){
                return [
                    'name' => $item->item->name,
                    'unit' => $item->item->unit,
                    'unit_price' => $item->item->unit_price,
                    'quantity' => $item->quantity,
                    'image' => $item->item->image
                ];
            }),
        ];
    }
}
