<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DiscountResource extends JsonResource
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
            'categories' => json_decode($this->categories),
            //'categories_details'=>$this->categories_details ?? null,
            'items' => json_decode($this->items),
            //'items_details'=>$this->items_details ?? null,
            'title' => $this->title,
            'is_percentage' => (bool)$this->is_percentage,
            'amount' => $this->amount,
            'discount_schedule' => json_decode($this->discount_schedule),
            'duration_type' => $this->duration_type,
            'duration_type_alis' => $this->getDurationAlisName($this->duration_type),
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'max_use' => $this->max_use,
            'use_count' => $this->use_count,
        ];
    }

    public function getDurationAlisName($type): string
    {
        return match ($type) {
            1 => 'forever',
            2 => 'date-range',
            default => 'invalid',
        };

    }
}
