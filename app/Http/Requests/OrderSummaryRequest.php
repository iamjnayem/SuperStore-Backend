<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class OrderSummaryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'user_id' => auth()->user()->id,
            'invoice_id' => strtoupper(Str::random(10)),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'user_id' => 'required',
            'merchant_id' => 'required|numeric',
            'invoice_id' => 'unique:orders|required',
            'payable' => 'required|numeric',
            'pickup_date' => 'nullable|date_format:Y-m-d',
            'pickup_time' => 'nullable',
            'has_discount' => 'nullable',
            'items'=>'array',
            'items.*.id'=>'required',
            'items.*.quantity'=>'required|numeric',
            'items.*.discount_id'=>'nullable|numeric',
            'items.*.addons'=>'nullable|array',
            'items.*.addons.*.name'=>'required',
            'items.*.addons.*.items'=>'nullable|array',
            'items.*.addons.*.items.*.name'=>'required',
            'items.*.addons.*.items.*.unit_price'=>'required',
            'order_type' => 'required|'. Rule::in(config('default.order_type.types')),
            'user_note' => 'nullable|string'
        ];
    }
}
