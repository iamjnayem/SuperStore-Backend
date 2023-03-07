<?php

namespace App\Http\Resources;

use App\Http\Traits\DiscountTrait;
use App\Http\Traits\ItemPublishStatus;
use Illuminate\Http\Resources\Json\JsonResource;

class MarketPlaceItemResource extends JsonResource
{
    use ItemPublishStatus, DiscountTrait;

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
            "id"=> $this->id,
            "user_id"=> $this->user_id,
            "category_id"=> $this->category_id,
            "name"=> $this->name,
            "unit"=> $this->unit,
            "unit_price"=> number_format($this->unit_price,2,'.'),
            "total_price"=> number_format(($this->unit_price - $this->getItemDiscountAmount($this->id,$this->user_id)),'2','.'),
            "total_discount"=> number_format(($this->getItemDiscountAmount($this->id,$this->user_id)),2,'.'),
            "image"=> $this->image,
            "barcode"=> $this->barcode,
            "description"=> $this->description,
            "stock_quantity"=> $this->stock_quantity,
            "low_stock_alert"=> $this->low_stock_alert,
            "status"=> !!$this->status,
            "is_publish"=> $this->getPublishStatus( $this->is_publish),
            "created_at"=> $this->created_at,
            "updated_at"=> $this->updated_at,
            "addons"=> json_decode($this->addons),
            "category"=> [
                "id"=> $this->category->id,
                "user_id"=> $this->category->user_id,
                "name"=> $this->category->name,
                "description"=> $this->category->description,
                "status"=> $this->category->status,
                "created_at"=> $this->category->created_at,
                "updated_at"=> $this->category->updated_at
            ],
            "sales_report" => $this->sales_report,
            "discount" => $this->discount ? [
                    "id" => $this->discount->id,
                    "title" => $this->discount->title,
                    "is_percentage" => $this->discount->is_percentage,
                    "amount" => $this->discount->amount,
                    "discount_schedule" => json_decode($this->discount->discount_schedule),
                    "duration_type" => $this->discount->duration_type,
                    "start_date" => $this->discount->start_date,
                    "end_date" => $this->discount->end_date,
                ]: null

        ];
    }
}
