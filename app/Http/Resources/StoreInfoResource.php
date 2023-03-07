<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Log;

class StoreInfoResource extends JsonResource
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
            'address' => json_decode($this->address),
            'allow_pickup' => !! $this->allow_pickup,
            'allow_dine_in' => !! $this->allow_dine_in,
            'pickup_and_dine_in_times' => json_decode($this->pickup_and_dine_in_times),
            'order_prepare_time' => $this->order_prepare_time,
            'allow_schedule_pickup' => !! $this->allow_schedule_pickup,
            'instructions' => $this->instructions,
            'is_store_enable' => !!$this->is_store_enable
        ];
    }
}
