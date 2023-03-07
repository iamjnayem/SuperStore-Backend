<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;

class ItemStoreRequest extends FormRequest
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
            'category_id' => 'required',
            'name' => 'required',
            'unit' => 'required',
            'unit_price' => 'required|numeric|gt:0',
            'description' => 'nullable',
            'low_stock_alert' => 'nullable',
            'stock_quantity' => 'required',
            // 'image' => 'required|mimes:jpeg,jpg,png',
            'barcode' =>'nullable',
            'addons' => 'nullable|array',
            'addons.*.name' => 'required|string',
            'addons.*.is_radio_button' => 'required|boolean',
            'addons.*.is_required' => 'required|boolean',
            'addons.*.items' => 'required|array',
            'addons.*.items.*.name' => 'required|string|max:255',
            'addons.*.items.*.unit_price' => 'required|numeric|gte:0',
        ];
    }
}
