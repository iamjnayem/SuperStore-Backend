<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSettingsRequest extends FormRequest
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
        if ($this->address){
            $this->merge([
                'address' => json_encode($this->address)
            ]);
        }

        if ($this->pickup_and_dine_in_times){
            $this->merge([
                'pickup_and_dine_in_times' => json_encode($this->pickup_and_dine_in_times)
            ]);
        }

        $this->merge([
            'user_id' => auth()->user()->id
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
            'user_id'=>'required',
            'address'=>'nullable|json',
            'allow_pickup'=>'nullable|boolean',
            'allow_dine_in'=>'nullable|boolean',
            'pickup_and_dine_in_times'=>'nullable|json',
            'order_prepare_time'=>'nullable|string',
            'allow_schedule_pickup'=>'nullable|boolean',
            'instructions'=>'nullable|string',
        ];
    }
}
