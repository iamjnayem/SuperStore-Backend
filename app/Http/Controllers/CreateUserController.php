<?php

namespace App\Http\Controllers;

use App\Http\Traits\RequestTrait;
use App\Models\User;
use Illuminate\Foundation\Bootstrap\LoadConfiguration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CreateUserController extends Controller
{
    use RequestTrait;

    public function __construct(Request $request)
    {
        $this->setSettings($request);
    }

    public function create_user_by_deshi()
    {
        $rules = [
            'name' => 'required',
            'email' => 'unique:users|nullable',
            'mobile_no' => 'required|unique:users',
            'avatar' => 'nullable',
            'deshi_user_id' => 'required|unique:users',
            'user_type' => 'required',
            'is_store_enable' => 'nullable',
            "business_category_id" => "required_if:user_type,==,merchant"
        ];
        try {

            if ($this->request->has('mobile_no')){
                $this->request->mobile_no = Str::startsWith($this->request->mobile_no,'+880') ? $this->request->mobile_no : '+88'.$this->request->mobile_no;
            }

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
                    'business_category_id',
                    'is_store_enable',
                ]
            );
            $user = User::create($arr);
            if($user){
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
}
