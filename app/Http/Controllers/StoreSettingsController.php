<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSettingsRequest;
use App\Http\Resources\StoreInfoResource;
use App\Http\Traits\RequestTrait;
use App\Models\StoreSettings;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;



class StoreSettingsController extends Controller
{
    use RequestTrait;


    public function storeInfo():JsonResponse
    {
        try{
            $storeInfo = StoreSettings::where('user_id',auth()->user()->id)->firstOrFail();

            if ($storeInfo){
                $store_activate_info = User::where(["id" => auth()->user()->id])->first('is_store_enable');
                $is_store_enable = $store_activate_info->is_store_enable;
                $storeInfo['is_store_enable'] = $is_store_enable;
                return $this->SuccessResponse(new StoreInfoResource($storeInfo));
            }

        }catch(ModelNotFoundException $exception){
            $this->writeErrors($exception);
            return $this->FailResponse([], ['no store settings info found'], 422);
        }
         catch (\Exception $exception){
            $this->writeErrors($exception);
        }
        return $this->FailResponse($this->message['data'],$this->message['messages'],$this->message['status'],$this->message['custom_code'],$this->message['validation']);

    }

    public function storeAddressSettings(StoreSettingsRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $storeInfo = StoreSettings::where('user_id',$data['user_id'])->first();

            if ($storeInfo){
                $storeInfo->update([
                    'address' => $data['address']
                ]);
            }
            else{
                StoreSettings::create([
                    'user_id' => $data['user_id'],
                    'address' => $data['address']
                ]);
            }
            return $this->SuccessResponse();

        }catch (\Exception $exception){
            $this->writeErrors($exception);
        }
        return $this->FailResponse($this->message['data'],$this->message['messages'],$this->message['status'],$this->message['custom_code'],$this->message['validation']);
    }

    public function allowServices(StoreSettingsRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $storeInfo = StoreSettings::where('user_id',$data['user_id'])->first();

            if ($storeInfo){
                $storeInfo->update([
                    'allow_pickup' => $data['allow_pickup'],
                    'allow_dine_in' => $data['allow_dine_in']
                ]);
            }
            else{
                StoreSettings::create([
                    'user_id' => $data['user_id'],
                    'allow_pickup' => $data['allow_pickup'],
                    'allow_dine_in' => $data['allow_dine_in']
                ]);
            }
            return $this->SuccessResponse();

        }catch (\Exception $exception){
            $this->writeErrors($exception);
        }
        return $this->FailResponse($this->message['data'],$this->message['messages'],$this->message['status'],$this->message['custom_code'],$this->message['validation']);
    }

    public function orderPrepareTime(StoreSettingsRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $storeInfo = StoreSettings::where('user_id',$data['user_id'])->first();

            if ($storeInfo){
                $storeInfo->update([
                    'order_prepare_time' => $data['order_prepare_time']
                ]);
            }
            else{
                StoreSettings::create([
                    'user_id' => $data['user_id'],
                    'order_prepare_time' => $data['order_prepare_time']
                ]);
            }
            return $this->SuccessResponse();

        }catch (\Exception $exception){
            $this->writeErrors($exception);
        }
        return $this->FailResponse($this->message['data'],$this->message['messages'],$this->message['status'],$this->message['custom_code'],$this->message['validation']);
    }

    public function allowSchedulePickup(StoreSettingsRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $storeInfo = StoreSettings::where('user_id',$data['user_id'])->first();

            if ($storeInfo){
                $storeInfo->update([
                    'allow_schedule_pickup' => $data['allow_schedule_pickup']
                ]);
            }
            else{
                StoreSettings::create([
                    'user_id' => $data['user_id'],
                    'allow_schedule_pickup' => $data['allow_schedule_pickup']
                ]);
            }
            return $this->SuccessResponse();

        }catch (\Exception $exception){
            $this->writeErrors($exception);
        }
        return $this->FailResponse($this->message['data'],$this->message['messages'],$this->message['status'],$this->message['custom_code'],$this->message['validation']);
    }

    public function instructionSettings(StoreSettingsRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $storeInfo = StoreSettings::where('user_id',$data['user_id'])->first();

            if ($storeInfo){
                $storeInfo->update([
                    'instructions' => $data['instructions']
                ]);
            }
            else{
                StoreSettings::create([
                    'user_id' => $data['user_id'],
                    'instructions' => $data['instructions']
                ]);
            }
            return $this->SuccessResponse();

        }catch (\Exception $exception){
            $this->writeErrors($exception);
        }
        return $this->FailResponse($this->message['data'],$this->message['messages'],$this->message['status'],$this->message['custom_code'],$this->message['validation']);
    }

    public function pickupDineInTimeSet(StoreSettingsRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $storeInfo = StoreSettings::where('user_id',$data['user_id'])->first();

            if ($storeInfo){
                $storeInfo->update([
                    'pickup_and_dine_in_times' => $data['pickup_and_dine_in_times']
                ]);
            }
            else{
                StoreSettings::create([
                    'user_id' => $data['user_id'],
                    'pickup_and_dine_in_times' => $data['pickup_and_dine_in_times']
                ]);
            }
            return $this->SuccessResponse();

        }catch (\Exception $exception){
            $this->writeErrors($exception);
        }
        return $this->FailResponse($this->message['data'],$this->message['messages'],$this->message['status'],$this->message['custom_code'],$this->message['validation']);
    }


    /*
     * get Store Order Time
     *
     * */
    public function storeOrderTime()
    {
        $storeInfo = json_decode(StoreSettings::where('user_id',auth()->user()->id)->value('pickup_and_dine_in_times'));

        if($storeInfo){
            return $this->SuccessResponse($storeInfo);
        }
        return $this->FailResponse([], ['store order time not found'], 422);

    }

    public function enableOrDisableStore(Request $request):JsonResponse{
        if(!$request->has('is_store_enable')){
            return $this->FailResponse([], ["is_store_enable field is required"], 422);
        }

        try{
            $store_activate_info = $request->json()->all();
            $user = User::where('id', auth()->user()->id)->first();

            if($user){
                $user->update(["is_store_enable" => $store_activate_info['is_store_enable']]);
                return $this->SuccessResponse([], ["Success"], 200);

            }else{
                return $this->FailResponse([], ["Failed"], 500);
            }

        }
        catch (\Exception $exception){
            $this->writeErrors($exception);
        }
        return $this->FailResponse($this->message['data'],$this->message['messages'],$this->message['status'],$this->message['custom_code'],$this->message['validation']);

    }
}
