<?php

namespace App\Http\Resources\KAM;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
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
            'desi_user_id' => (int) $this->user?->deshi_user_id,
            'category_id' => $this->category_id,
            'name' => $this->name,
            'description' => $this->description,
            'unit' => $this->unit,
            'unit_price' => (float) $this->unit_price,
            'image' => $this->image,
            'stock_quantity' => $this->stock_quantity,
            'addons' => $this->addons,
            'is_publish' => (int) $this->is_publish,
            'status' => $this->status,
            'publish_status' => $this->getPublishStatus(),
            'category' => $this->category,
            'merchant' => $this->getMerchantData(),
            'discount' => $this->discount,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }


    public function getPublishStatus()
    {
        return match ((int) $this->is_publish) {
            1 => 'requested',
            2 => 'approved',
            default => 'unapproved',
        };
    }


    public function getMerchantData()
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'mobile_no' => $this->mobile_no,
            'desi_user_id' => $this->deshi_user_id,
            'avatar' => $this->avatar,
            'total_items' =>  $this->user?->shopItems()->count(),
            'requested_items' =>  $this->user?->shopItems()->filterByRequested()->count(),
            'verified_items' =>  $this->user?->shopItems()->filterByPublished()->count(),
        ];
    }


}
