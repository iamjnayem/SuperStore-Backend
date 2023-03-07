<?php

namespace App\Http\Controllers\Kam;

use App\Http\Controllers\Controller;
use App\Http\Resources\ItemListResource;
use App\Http\Traits\ItemFilterTrait;
use App\Http\Traits\RequestTrait;
use App\Models\Item;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class KamItemController extends Controller
{
    const ITEM_PUBLISHED = 2;
    const ITEM_UNPUBLISHED = 0;
    const ITEM_REQUESTED = 1;

    use RequestTrait,ItemFilterTrait;

    public function __construct(Request $request)
    {
        $this->setSettings($request);
    }

    /*
     * Shop published and requested item list
     *
     * */
    public function shopItem(): JsonResponse
    {
        try {
            $item = Item::with('category')
                ->when(!request()->query('filter'), function ($q) {
                    $q->filterByShopItem();
                })
                ->when(request()->query('filter') == "requested", function ($q) {
                    $q->filterByRequested();
                })
                ->when(request()->query('filter') == "published", function ($q) {
                    $q->filterByPublished();
                })
                ->orderBy('id','desc')
                ->get();

            if($item){
                return $this->SuccessResponse(ItemListResource::collection($item)->response()->getData(true));
            }
        }catch (ValidationException $exception){
            $this->configureException($exception);
        }catch (\Exception $exception){
            $this->writeErrors($exception);
        }
        return $this->FailResponse($this->message['data'],$this->message['messages'],$this->message['status'],$this->message['custom_code'],$this->message['validation']);

    }

    public function itemStatusUpdate( $id): JsonResponse
    {
        $item = Item::where('id',$id)->first();
        try {
            if (request()->query('publish') == 'active'){
                Item::where('id',$item->id)
                    ->update([
                        'is_publish' => self::ITEM_PUBLISHED
                    ]);
                return $this->SuccessResponse(['Item hase been published successful.']);
            }
            elseif (request()->query('publish') == 'inactive'){
                Item::where('id',$item->id)
                    ->update([
                        'is_publish' => self::ITEM_UNPUBLISHED
                    ]);
                return $this->SuccessResponse(['Item inactive request successfully.']);
            }
            elseif (request()->query('publish') == 'requested'){
                Item::where('id',$item->id)
                    ->update([
                        'is_publish' => self::ITEM_REQUESTED
                    ]);
                return $this->SuccessResponse(['Item publish request successfully.']);
            }
            return $this->FailResponse([],$this->message['Invalid item request'],422,$this->message['custom_code'],$this->message['validation']);

        }catch (\Exception $exception){
            $this->writeErrors($exception);
        }
        return $this->FailResponse([],$this->message['Item not found'],422,$this->message['custom_code'],$this->message['validation']);
    }
}
