<?php

namespace App\Http\Controllers;

use App\Http\Traits\RequestTrait;
use App\Models\PrivacySetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class PrivacySettingController extends Controller
{
    use RequestTrait;

    public function __construct(Request $request)
    {
        $this->setSettings($request);
    }


    public function index():JsonResponse
    {
        try {
            $settings = PrivacySetting::where('user_id',auth()->user()->id)->first();

            if($settings){
                return $this->SuccessResponse($settings);
            } else return $this->SuccessResponse([
                "is_email_visible"=>true,
                "is_address_visible" => true
            ]);
        }catch (ValidationException $exception){
            $this->configureException($exception);
        }catch (\Exception $exception){
            $this->writeErrors($exception);
        }
        return $this->FailResponse($this->message['data'],$this->message['messages'],$this->message['status'],$this->message['custom_code'],$this->message['validation']);
    }


    public function store(Request $request): JsonResponse
    {
        try {

            $validated = $request->validate([
                'is_email_visible' => 'required|boolean',
                'is_address_visible' => 'required|boolean'
            ]);

            $settings = PrivacySetting::updateOrCreate(
                ['user_id' => auth()->user()->id],
                [
                    'is_email_visible' => $request->is_email_visible,
                    'is_address_visible' => $request->is_address_visible,
                    'user_id' => auth()->user()->id
                ]
            );

            return $this->SuccessResponse($settings);

        }catch (ValidationException $exception){
            $this->configureException($exception);
        }catch (\Exception $exception){
            $this->writeErrors($exception);
        }
        return $this->FailResponse($this->message['data'],$this->message['messages'],$this->message['status'],$this->message['custom_code'],$this->message['validation']);
    }
}
