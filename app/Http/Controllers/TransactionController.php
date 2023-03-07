<?php

namespace App\Http\Controllers;

use App\Http\Traits\RequestTrait;
use App\Models\User;
use Illuminate\Foundation\Bootstrap\LoadConfiguration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class TransactionController extends Controller
{
    use RequestTrait;

    public function __construct(Request $request)
    {
        $this->setSettings($request);
    }

    public function crate_user_by_deshi()
    {
        $rules = [
            'name' => 'required',
            'email' => 'required',
            'mobile_no' => 'required',
            'avatar' => 'required',
            'deshi_user_id' => 'required',
            'user_type' => 'required',
        ];
        try {
            $validation = Validator::make($this->request->all(), $rules);
            if ($validation->fails()) {
                throw new ValidationException($validation);
            }
            $arr = $this->request->only(
                [
                    'name',
                    'email',
                    'mobile_no',
                    'avatar',
                    'deshi_user_id',
                    'user_type',
                ]
            );
            $user = User::create($arr);
            if($user){
                return $this->SuccessResponse();
            }
        }catch (ValidationException $exception){
            $this->configureException($exception);
        }catch (\Exception $exception){
            $this->writeErrors($exception);
        }
        return $this->FailResponse($this->message['data'],$this->message['messages'],$this->message['status'],$this->message['custom_code'],$this->message['validation']);
    }
}
