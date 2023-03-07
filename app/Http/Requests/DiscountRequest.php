<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DiscountRequest extends FormRequest
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
           'merchant_id' => auth()->user()->id
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
            'merchant_id' => 'required',
            'title' => 'required',
            'is_percentage' => 'required|in:0,1',
            'amount' => 'required|numeric|gt:0',
            'duration_type' => 'required|in:1,2',
            'start_date' => 'nullable|date_format:Y-m-d H:i:s',
            'end_date' => 'nullable|date_format:Y-m-d H:i:s',
            'max_use' => 'nullable|integer|gt:0',
            'discount_schedule' => 'required|array',
            'discount_schedule.saturday' => 'nullable',
            'discount_schedule.sunday' => 'nullable',
            'discount_schedule.monday' => 'nullable',
            'discount_schedule.tuesday' => 'nullable',
            'discount_schedule.wednesday' => 'nullable',
            'discount_schedule.thursday' => 'nullable',
            'discount_schedule.friday' => 'nullable',
            'discount_schedule.*.start_time' => 'nullable|date_format:H:i:s',
            'discount_schedule.*.end_time' => 'nullable|date_format:H:i:s',
            'discount_schedule.*.is_open' => 'nullable|in:0,1',

            'categories' => 'nullable|array|' . Rule::exists('categories', 'id')
                    ->where('user_id', auth()->user()->id),
            'items' => 'nullable|array|'. Rule::exists('items', 'id')
                    ->where('user_id', auth()->user()->id),
        ];
    }
}
