<?php

namespace App\Http\Controllers\Merchant;

use App\Constants\UserType;
use App\Http\Controllers\Controller;
use App\Http\Resources\ItemViewResource;
use App\Http\Resources\MarketPlaceItemResource;
use App\Http\Resources\ShopItemListResource;
use App\Http\Traits\DiscountTrait;
use App\Http\Traits\ItemPublishStatus;
use App\Http\Traits\RequestTrait;
use App\Http\Traits\ShopTimeValidationTrait;
use App\Models\Category;
use App\Models\Item;
use App\Models\StoreSettings;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class MarketPlaceController extends Controller
{
    use RequestTrait, ItemPublishStatus, DiscountTrait,ShopTimeValidationTrait;

    public const INACTIVE = 0;
    public const REQUESTED = 1;
    public const PUBLISHED = 2;

    public function getItems(): JsonResponse
    {
        try {
            $items = Item::with('discount')->where('user_id',auth()->user()->id)
                ->where('is_publish', self::PUBLISHED)
                ->where('addons','!=', null)
                ->orderBy('id','desc')
                ->get();

            return $this->SuccessResponse(MarketPlaceItemResource::collection($items),['Success'],200);

        }catch (ValidationException $exception){
            $this->configureException($exception);
        }catch (\Exception $exception){
            $this->writeErrors($exception);
        }
        return $this->FailResponse($this->message['data'],$this->message['messages'],$this->message['status'],$this->message['custom_code'],$this->message['validation']);

    }


    public function shopItems($shopId): JsonResponse
    {
        try {
            $getShopId = User::where('deshi_user_id',$shopId)->first();

            if (!$getShopId){
                return $this->SuccessResponse([],['Success'],200);
            }

            $items = $this->getItemList($getShopId);

            $grouped = $items->groupBy('itemCategory.name')
                ->map(function ($shop){
                    return $shop->take(8);
                });
            //Log::debug();

            $groupData = $grouped->all();

            $dataValues = array_values($groupData);
            $dataKeys = array_keys($groupData);

            $data = [];

            foreach ($dataKeys as $i => $key){
                $data[$i] =[
                    'category_name' => $key,
                    'category_id' => $dataValues[$i][0]['itemCategory']['id'],
                    'items' =>MarketPlaceItemResource::collection($dataValues[$i]),
                ];
            }

            return $this->SuccessResponse($data,['Success'],200);
            //return $this->SuccessResponse(MarketPlaceItemResource::collection($data),['Success'],200);

        }catch (ValidationException $exception){
            $this->configureException($exception);
        }catch (\Exception $exception){
            $this->writeErrors($exception);
        }
        return $this->FailResponse($this->message['data'],$this->message['messages'],$this->message['status'],$this->message['custom_code'],$this->message['validation']);

    }

    public function merchantList(): JsonResponse
    {
        try {

            $query = DB::table('users')
                ->leftJoin('store_settings','users.id','=','store_settings.user_id')
                ->select('users.id','users.deshi_user_id','store_settings.pickup_and_dine_in_times')
                ->where('users.user_type',UserType::MERCHANT)
                ->where('users.is_store_enable',true)
                ->where('users.status',1)
                ->where('users.deshi_user_id','!=',null)
                ->where('store_settings.pickup_and_dine_in_times','!=',null)
                ->get();

            $merchantList = [];

            foreach ($query as $data){
                if($this->checkStoreStartAndEndTime((array)json_decode($data->pickup_and_dine_in_times))){
                    $merchantList[] = $data->deshi_user_id;
                }
            }

            //$merchantList = $query->pluck('deshi_user_id');

            return $this->SuccessResponse(collect($merchantList),['Success'],200);

        }catch (ValidationException $exception){
            $this->configureException($exception);
        }catch (\Exception $exception){
            $this->writeErrors($exception);
        }
        return $this->FailResponse($this->message['data'],$this->message['messages'],$this->message['status'],$this->message['custom_code'],$this->message['validation']);

    }
    /*
     * Bussines Category List
     *
     * */
    public function bussinesCategoryList(): JsonResponse
    {
        try {

            $businessCategoryId = User::where('user_type',UserType::MERCHANT)
                ->where('is_store_enable',true)
                ->where('status',1)
                ->where('business_category_id','!=',0)
                ->get()->pluck('business_category_id')->unique();

            return $this->SuccessResponse($businessCategoryId->values()->all(),['Success'],200);

        }catch (ValidationException $exception){
            $this->configureException($exception);
        }catch (\Exception $exception){
            $this->writeErrors($exception);
        }
        return $this->FailResponse($this->message['data'],$this->message['messages'],$this->message['status'],$this->message['custom_code'],$this->message['validation']);

    }

    /*
     * Shop Category List
     *
     * */
    public function shopCategoryList($id): JsonResponse
    {
        try {
            $shop = User::where('deshi_user_id',$id)
                ->where('user_type',UserType::MERCHANT)
                ->where('is_store_enable',true)
                ->where('status',1)
                ->where('business_category_id','!=',0)
                ->first();

            $businessCategoryId = Category::where('user_id',$shop->id)
                ->select(['id','name'])
                ->where('status',1)
                ->get();

            return $this->SuccessResponse($businessCategoryId,['Success'],200);

        }catch (ValidationException $exception){
            $this->configureException($exception);
        }catch (\Exception $exception){
            $this->writeErrors($exception);
        }
        return $this->FailResponse($this->message['data'],$this->message['messages'],$this->message['status'],$this->message['custom_code'],$this->message['validation']);

    }

    public function show($shopId,$id)
    {
        try {
            $getShopId = User::where('deshi_user_id',$shopId)->first();

            if (!$getShopId){
                return $this->SuccessResponse([],['Success'],200);
            }

            $items = Item::with('discount')
                ->where('id',$id)
                ->where('is_publish', self::PUBLISHED)
                //->where('addons','!=', null)
                ->where('user_id',$getShopId->id)
                ->when(request()->query('category'), function ($q) {
                    $q->where('category_id',request()->query('category'));
                })
                ->orderBy('id','desc')
                ->first();

            return $this->SuccessResponse(new MarketPlaceItemResource($items),['Success'],200);

        }catch (ValidationException $exception){
            $this->configureException($exception);
        }catch (\Exception $exception){
            $this->writeErrors($exception);
        }
        return $this->FailResponse($this->message['data'],$this->message['messages'],$this->message['status'],$this->message['custom_code'],$this->message['validation']);

    }

    /*
     *
     * get store info by store id
     * */
    public function storeInfo($storeId): JsonResponse
    {
        try {
            $storeInfo = null;
            $getStoreId = User::where('deshi_user_id',$storeId)->first();


            if ($getStoreId){
                $storeInfo = StoreSettings::where('user_id',$getStoreId->id)
                    ->select('id','user_id','address','allow_pickup','allow_dine_in','pickup_and_dine_in_times','order_prepare_time','allow_schedule_pickup','instructions')
                    ->first();

                $storeInfo['address'] = json_decode($storeInfo->address);

                $storeInfo['store_id'] = $getStoreId->deshi_user_id;
            }

            return $this->SuccessResponse($storeInfo,['Success'],200);

        }catch (ValidationException $exception){
            $this->configureException($exception);
        }catch (\Exception $exception){
            $this->writeErrors($exception);
        }
        return $this->FailResponse($this->message['data'],$this->message['messages'],$this->message['status'],$this->message['custom_code'],$this->message['validation']);


    }


    public function shopItemSearch($shopId)
    {
        try {
            $getShopId = User::where('deshi_user_id',$shopId)->first();

            if (!$getShopId){
                return $this->SuccessResponse([],['Success'],200);
            }

            $items = $this->getItemList($getShopId);


            //return $this->SuccessResponse($data,['Success'],200);
            return $this->SuccessResponse(MarketPlaceItemResource::collection($items),['Success'],200);

        }catch (ValidationException $exception){
            $this->configureException($exception);
        }catch (\Exception $exception){
            $this->writeErrors($exception);
        }
        return $this->FailResponse($this->message['data'],$this->message['messages'],$this->message['status'],$this->message['custom_code'],$this->message['validation']);

    }


    public function getItemList($getShopId)
    {
        return Item::with('discount','itemCategory')
            ->where('is_publish', self::PUBLISHED)
            //->where('addons','!=', null)
            ->where('user_id',$getShopId->id)
            ->when(request()->query('category'), function ($q) {
                $q->where('category_id',request()->query('category'));
            })
            ->when(request()->query('search'), function ($q) {
                $q->search();
            })
            ->orderBy('id','desc')
            ->get();
    }

}
