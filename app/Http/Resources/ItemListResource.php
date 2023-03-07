<?php

namespace App\Http\Resources;

use App\Http\Traits\ItemPublishStatus;
use Illuminate\Http\Resources\Json\JsonResource;

class ItemListResource extends JsonResource
{
    use ItemPublishStatus;
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        //return parent=>=>toArray($request);
        return [
            "id"=> $this->id,
            "user_id"=> $this->user_id,
            "category_id"=> $this->category_id,
            "name"=> $this->name,
            "unit"=> $this->unit,
            "unit_price"=> $this->unit_price,
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
            ]
        ];
    }
}
