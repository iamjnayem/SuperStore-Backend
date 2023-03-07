<?php
namespace App\Http\Traits;

use Carbon\Carbon;

trait InvoiceTransactionTrait{

    public $quantity = 1;
    public $discount = 0;

    public function creditAccount($invoice,$transactionId,$deshi_transaction_id)
    {
        return[
            'user_id' => $invoice->merchant->id,
            'invoice_id' => $invoice->id,
            'transaction_id' => $transactionId,
            'deshi_transaction_id' => $deshi_transaction_id,
            'type'=>'credit',
            'type_human_readable'=>'Deposit',
            'amount'=>$invoice->final_bill_amount,
            'discount' =>$invoice->discount_amount
        ];
    }

    public function debitAccount($invoice,$transactionId,$deshi_transaction_id)
    {
        return[
            'user_id' => $invoice->user->id,
            'invoice_id' => $invoice->id,
            'transaction_id' => $transactionId,
            'deshi_transaction_id' => $deshi_transaction_id,
            'type'=>'debit',
            'type_human_readable'=>'Deducted',
            'amount'=>$invoice->final_bill_amount,
            'discount' =>$invoice->discount_amount
        ];

    }

    public function itemInformation($invoice)
    {
        $this->quantity = $this->countTotalQuantity($invoice->invoice_items);
        $this->discount = $invoice->discount_amount;
        return $invoice->invoice_items->map(function($item){
            $discountAmount = $this->discount > 0 ? ($this->discount/$this->quantity) : 0 ;
            $withoutDiscountUnitPrice = (($item->item->unit_price) - $discountAmount );
            return [
                'item_id' => $item->item->id,
                'name' => $item->item->name,
                'category_id' => $item->item->category_id,
                'unit' => $item->item->unit,
                'unit_price' => $item->item->unit_price,
                'unit_price_without_discount' => $withoutDiscountUnitPrice,
                'total_price'=>floatval($item->item->unit_price)*$item->quantity,
                'total_price_without_discount'=>floatval($withoutDiscountUnitPrice)*$item->quantity,
                'quantity' => $item->quantity,
                'image' => $item->item->image
            ];
        });
    }

    public function categoryInformation($invoice)
    {
        $this->quantity = $this->countTotalQuantity($invoice->invoice_items);
        $this->discount = $invoice->discount_amount;
        return $invoice->invoice_items->map(function($item){
            $discountAmount = $this->discount > 0 ? ($this->discount/$this->quantity) : 0 ;
            $withoutDiscountUnitPrice = (($item->item->unit_price) - $discountAmount );
            return [
                'category_id' => $item->item->category_id,
                'item_quantities' => $item->quantity,
                'total_price'=>floatval($item->item->unit_price)*$item->quantity,
                'total_price_without_discount'=>floatval($withoutDiscountUnitPrice)*$item->quantity,
            ];
        });
    }

    public function countTotalQuantity($invoice)
    {
        $quantity = 0;
        foreach ($invoice as $item){
            $quantity = $quantity + $item->quantity;
        }
        return $quantity;
    }


    public function get_invoice($invoice)
    {
        return [
            'id' => $invoice->id,
            'invoice_id' => $invoice->invoice_id,
            'custom_invoice_id' => $invoice->invoice_id,
            'title' => $invoice->title,
            'total_bill_amount' => $invoice->total_bill_amount,
            'final_bill_amount' => $invoice->final_bill_amount,
            'discount_amount' => $invoice->discount_amount,
            'delivery_charge_amount' => $invoice->delivery_charge_amount,
            'vat_tax_amount' => $invoice->vat_tax_amount,
            'expire_after' => $invoice->expire_after,
            'reminder' => $invoice->reminder,
            'notes' => $invoice->notes,
            'status' => $this->getStatus($invoice->status),
            'status_alias' => $this->getStatusAliasName($invoice->status),
            'head' => "Invoice " . $this->getStatus($invoice->status),
            'schedule_at_date' => $invoice->schedule_at_date,
            'schedule_at_time' => $invoice->schedule_at_time,
            'created_at_date' => Carbon::createFromFormat('Y-m-d H:i:s', $invoice->created_at)->format('Y-m-d'),
            'created_at_time' => Carbon::createFromFormat('Y-m-d H:i:s', $invoice->created_at)->format('H:i:s'),
            'notification_method' => explode(',',$invoice->notification_method),
            'merchant' => [
                'id' => $invoice->merchant->id,
                'name' => $invoice->merchant->name,
                'avatar' => $invoice->merchant->avatar,
                'mobile_no' => $invoice->merchant->mobile_no,
                'user_type' => $invoice->merchant->user_type,
                'address' => $invoice->merchant->address,
                'email' => $invoice->merchant->email,
            ],
            'user' => [
                'id' => $invoice->user->id,
                'name' => $invoice->user->name,
                'avatar' => $invoice->user->avatar,
                'mobile_no' => $invoice->user->mobile_no,
                'user_type' => $invoice->user->user_type,
                'address' => $invoice->user->address,
                'email' => $invoice->user->email,
            ],
            'items' => $invoice->invoice_items->map(function($item){
                return [
                    'item_id' => $item->item->id,
                    'name' => $item->item->name,
                    'category_id' => $item->item->category_id,
                    'unit' => $item->item->unit,
                    'unit_price' => $item->item->unit_price,
                    'total_price'=>floatval($item->item->unit_price)*$item->quantity,
                    'quantity' => $item->quantity,
                    'image' => $item->item->image
                ];
            }),

            'category_info' => $invoice->invoice_items->map(function($item){
                return [
                    'category_id' => $item->item->category_id,
                    'item_quantities' => $item->quantity,
                    'total_price'=>floatval($item->item->unit_price)*$item->quantity,
                ];
            }),
        ];
    }


}
