<?php

namespace App\Http\Requests\Kam;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;

class ItemUpdateRequest extends FormRequest
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

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'product_id' => 'required|exists:items,id',
            'status' => 'required|in:0,1,2',
        ];
    }



    /**
     * @param Validator $validator
     * @return void
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException($this->formatErrors($validator));
    }



    /**
     * @param Validator $validator
     * @return JsonResponse
     */
    public function formatErrors($validator)
    {
        return new JsonResponse(
            [
                'message' => $validator->errors()->first(),
                'errors' => Arr::undot($validator->errors()->all())
            ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY
        );
    }


}
