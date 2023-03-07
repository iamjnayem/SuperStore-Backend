<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
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
            'name' => $this->name,
            'unit_price' => number_format($this->unit_price,2,'.'),
            'unit' => $this->unit,
            'image' => $this->image,
            'barcode' => $this->barcode,
            'description' => $this->description,
            'quantity' =>$this->pivot->quantity,
            'addons' => $this->pivot->addons ? AddonsDetailsResource::collection(json_decode($this->pivot->addons)):null,
            'discount_details' => new DiscountDetailsResource(json_decode($this->pivot->discount_details)),
        ];
    }
}
