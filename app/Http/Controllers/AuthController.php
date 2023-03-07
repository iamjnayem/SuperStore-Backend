<?php

namespace App\Http\Controllers;

use App\Http\Traits\RequestTrait;
use App\Models\PrivacySetting;
use App\Models\User;
use Illuminate\Foundation\Bootstrap\LoadConfiguration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    use RequestTrait;

    public function __construct(Request $request)
    {
        $this->setSettings($request);
    }

    public function get_token_by_deshi($deshi_user_id)
    {
        try {
            $user = User::where('deshi_user_id',$deshi_user_id)->first();
            if($user) {
                $token = $user->createToken('basic');
                return $this->SuccessResponse([
                    'user' => $user,
                    'token' => $token->plainTextToken
                ]);
            }
        }catch (ValidationException $exception){
            $this->configureException($exception);
        }catch (\Exception $exception){
            $this->writeErrors($exception);
        }
        return $this->FailResponse($this->message['data'],$this->message['messages'],$this->message['status'],$this->message['custom_code'],$this->message['validation']);
    }

    public function   check_user_has_account_by_deshi($deshi_user_id)
    {
        try {
            $user = User::where('deshi_user_id',$deshi_user_id)->first();
            $data = ['availability' => false];
            if($user){
                $data ['availability'] = true;
                $data ['user'] = $user;
            }
            return $this->SuccessResponse($data);
        }catch (ValidationException $exception){
            $this->configureException($exception);
        }catch (\Exception $exception){
            $this->writeErrors($exception);
        }
        return $this->FailResponse($this->message['data'],$this->message['messages'],$this->message['status'],$this->message['custom_code'],$this->message['validation']);
    }

    public function check_user_has_account_by_id($id)
    {
        try {
            $user = User::where('id',$id)->first();
            $data = null;
            if($user){
                $data = $user;
            }
            return $this->SuccessResponse($data);
        }catch (ValidationException $exception){
            $this->configureException($exception);
        }catch (\Exception $exception){
            $this->writeErrors($exception);
        }
        return $this->FailResponse($this->message['data'],$this->message['messages'],$this->message['status'],$this->message['custom_code'],$this->message['validation']);
    }

    public function getUserSettings($deshi_user_id){
        try{
            $userId = User::where('deshi_user_id', $deshi_user_id)->value('id');
            if($userId){
                $privacySettings = PrivacySetting::select('is_email_visible', 'is_address_visible')->where('user_id', $userId)->first();
                if($privacySettings){
                    return $this->SuccessResponse($privacySettings);
                }else{
                    return $this->SuccessResponse([
                        "is_email_visible" => true,
                        "is_address_visible" => true
                    ]);
                }

            }else{
                return $this->FailResponse("privacy settings not found");
            }

        }catch (ValidationException $exception){
            $this->configureException($exception);
        }catch (\Exception $exception){
            $this->writeErrors($exception);
        }
        return $this->FailResponse($this->message['data'],$this->message['messages'],$this->message['status'],$this->message['custom_code'],$this->message['validation']);
    }

}
