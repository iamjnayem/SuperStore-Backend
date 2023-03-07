<?php

namespace App\Http\Controllers;

use App\Http\Requests\ItemStoreRequest;
use App\Http\Resources\ItemListResource;
use App\Http\Resources\ItemViewResource;
use App\Http\Traits\ItemFilterTrait;
use App\Http\Traits\RequestTrait;
use App\Models\Item;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Bootstrap\LoadConfiguration;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;


class ItemController extends Controller
{
    use RequestTrait, ItemFilterTrait;


    public function __construct(Request $request)
    {
        $this->setSettings($request);
    }


    public function store(ItemStoreRequest $request)
    {
        try {
            DB::beginTransaction();

            /*$file = \request()->file('image');
            $extension = $file->getClientOriginalExtension();
            $path = "item_images";
            $name = uniqid().".".$extension;
            $s3Path =  $file->storeAs($path,$name,'s3');
            $url = Storage::disk('s3')->url($s3Path);*/

            $validation = $request->validated();

            if (!request('image')) {
                return response()->json(['messages' => 'image field is required']);
            }

            $file = base64_decode(request('image'));
            $path = "deshipay/item/images/";
            $name = uniqid() . request('image_name');


            Storage::disk(config('app.files_from'))->put($path . $name, $file);
            $url = config('app.asset_cdn') . $path . $name;

            $validation['image'] = $url;

            if (array_key_exists('addons', $validation)) {
                $validation['addons'] = json_encode($validation['addons']);
            }

            //check for valid category
            $categories = Category::where('user_id', auth()->user()->id)->where('id', $request->category_id)->get();
            if (count($categories) == 0) {
                return $this->FailResponse([], ["invalid category id"], 422);
            }


            $item = Item::create($validation);

            DB::commit();

            if ($item) {
                $item['addons'] = json_decode($item['addons']);
                return $this->SuccessResponse($item);
            }
        } catch (ValidationException $exception) {
            DB::rollback();
            $this->configureException($exception);
        } catch (\Exception $exception) {
            DB::rollback();
            $this->writeErrors($exception);
        }
        return $this->FailResponse($this->message['data'], $this->message['messages'], $this->message['status'], $this->message['custom_code'], $this->message['validation']);
    }


    public function index()
    {

        try {
            $item = Item::with('category')
                ->when(request()->query('search'), function ($q) {
                    $q->search();
                })
                ->when(request()->query('filter') == "recently", function ($q) {
                    $q->filterByRecentAdded();
                })
                ->when(request()->query('filter') == "topseller", function ($q) {
                    $q->filterBytopSeller();
                })
                ->when(request()->query('filter') == "publish", function ($q) {
                    $q->filterByPublished();
                })
                ->when(request()->query('filter') == "unpublished", function ($q) {
                    $q->filterByUnpublished();
                })
                ->when(request()->query('filter') == "requested", function ($q) {
                    $q->filterByRequested();
                })
                ->when(request()->query('filter') == "alphabetic", function ($q) {
                    $q->sortByAlphabetic();
                })
                ->when(request()->query('category'), function ($q) {
                    $q->filterByCategoryId();
                })
                ->where(['user_id' => auth()->user()->id])
                ->orderBy('id', 'desc')
                ->paginate();

            return $this->SuccessResponse(ItemListResource::collection($item)->response()->getData(true));
        } catch (ValidationException $exception) {
            $this->configureException($exception);
        } catch (\Exception $exception) {
            $this->writeErrors($exception);
        }
        return $this->FailResponse($this->message['data'], $this->message['messages'], $this->message['status'], $this->message['custom_code'], $this->message['validation']);
    }


    public function show($id)
    {
        try {
            $item = Item::with('category', 'sales_report:item_id,total_sale_amount_without_discount as total_sales,sold_quantity')
                ->where(['user_id' => auth()->user()->id, 'id' => $id])
                ->first();

            if (!$item) {
                return $this->FailResponse([], ["invalid item id"], 422);
            }

            if ($item) {
                return $this->SuccessResponse(new ItemViewResource($item));
            }
        } catch (ValidationException $exception) {
            $this->configureException($exception);
        } catch (\Exception $exception) {
            $this->writeErrors($exception);
        }
        return $this->FailResponse($this->message['data'], $this->message['messages'], $this->message['status'], $this->message['custom_code'], $this->message['validation']);
    }


    public function delete($id)
    {
        try {
            $item = Item::where(['user_id' => auth()->user()->id, 'id' => $id])->delete();
            if ($item) {
                return $this->SuccessResponse();
            }
            $this->message['messages'] = ['item not found or already deleted'];
            $this->message['status'] = 422;
        } catch (ValidationException $exception) {
            $this->configureException($exception);
        } catch (\Exception $exception) {
            $this->writeErrors($exception);
        }
        return $this->FailResponse($this->message['data'], $this->message['messages'], $this->message['status'], $this->message['custom_code'], $this->message['validation']);
    }


    public function update(ItemStoreRequest $request, $id)
    {
        try {
            DB::beginTransaction();

            //check for valid category id
            $itemCategory = Item::where('id', $id)->where('user_id', auth()->user()->id)->get();

            if (count($itemCategory) == 0) {
                return $this->FailResponse([], ["invalid item id"], 422);
            }
            if ($itemCategory[0]->category_id != $request['category_id']) {
                return $this->FailResponse([], ["invalid category id"], 422);
            }

            $validation = $request->validated();



            if (array_key_exists('addons', $validation)) {
                $validation['addons'] = json_encode($validation['addons']);
            }

            if (request('image')) {
                $file = base64_decode(request('image'));
                $path = "deshipay/item/images/";
                $name = uniqid() . request('image_name');

                Storage::disk(config('app.files_from'))->put($path . $name, $file);
                $url = config('app.asset_cdn') . $path . $name;

                $validation['image'] = $url;
            }

            $item = Item::where('id', $id)
                ->where('user_id', auth()->user()->id)
                ->update($validation);

            DB::commit();

            $data = Item::with('category')
                ->where('id', $id)
                ->first();

            if ($item) {
                return $this->SuccessResponse($data);
            }
        } catch (ValidationException $exception) {
            DB::rollBack();
            $this->configureException($exception);
        } catch (\Exception $exception) {
            DB::rollBack();
            $this->writeErrors($exception);
        }
        return $this->FailResponse($this->message['data'], $this->message['messages'], $this->message['status'], $this->message['custom_code'], $this->message['validation']);
    }


    public function updateItemQuantity(Request $request, $id)
    {
        try {
            if (!$request->has('stock_quantity')) {
                return $this->FailResponse([], ["stock_quantity field is required"], 422);
            }
            $item = Item::where('user_id', auth()->user()->id)->where('id', $id)->first();


            if($item){
                Item::where('user_id', auth()->user()->id)
                ->where('id', $id)
                ->update([
                    'stock_quantity' => $request->stock_quantity
                ]);
                return $this->SuccessResponse([], ["stock quantity updated successfully"]);
            }else{
                return $this->FailResponse([], ["invalid item id"], 422);
            }

        } catch (ValidationException $exception) {
            $this->configureException($exception);
        } catch (\Exception $exception) {
            $this->writeErrors($exception);
        }
        return $this->FailResponse($this->message['data'], $this->message['messages'], $this->message['status'], $this->message['custom_code'], $this->message['validation']);
    }


    public function updateItemLowStockQuantity(Request $request, $id){
        try {
            if (!$request->has('low_stock_alert')) {
                return $this->FailResponse([], ["low_stock_alert field is required"], 422);
            }
            $item = Item::where('user_id', auth()->user()->id)->where('id', $id)->first();

            if($item){
                Item::where('user_id', auth()->user()->id)
                ->where('id', $id)
                ->update([
                    'low_stock_alert' => $request->low_stock_alert
                ]);
                return $this->SuccessResponse([], ["low_stock_alert quantity updated successfully"]);
            }else{
                return $this->FailResponse([], ["invalid item id"], 422);
            }
        } catch (ValidationException $exception) {
            $this->configureException($exception);
        } catch (\Exception $exception) {
            $this->writeErrors($exception);
        }
        return $this->FailResponse($this->message['data'], $this->message['messages'], $this->message['status'], $this->message['custom_code'], $this->message['validation']);
    }


    public function itemPublishRequest(Request $request, $item): JsonResponse
    {
        $item = Item::where('user_id', auth()->user()->id)->where('id', $item)->first();

        if (!$item) {
            return $this->FailResponse([], ['invalid item id'], 422);
        }


        try {
            if (request()->query('publish') == 'active') {
                if (!$item->is_publish) {
                    Item::where('user_id', auth()->user()->id)
                        ->where('id', $item->id)
                        ->update([
                            'is_publish' => 1
                        ]);
                    return $this->SuccessResponse(['Item publish request successfully.']);
                } else return $this->SuccessResponse(['Item already requested for publishing.']);
            } elseif (request()->query('publish') == 'inactive') {
                if ($item->is_publish) {
                    Item::where('user_id', auth()->user()->id)
                        ->where('id', $item->id)
                        ->update([
                            'is_publish' => 0
                        ]);
                    return $this->SuccessResponse(['Item inactive request successfully.']);
                } else return $this->SuccessResponse(['Item already requested for unpublished.']);
            }
            return $this->FailResponse([], $this->message['Invalid item request'], 422, $this->message['custom_code'], $this->message['validation']);
        } catch (\Exception $exception) {
            $this->writeErrors($exception);
        }
        return $this->FailResponse([], $this->message['Item not found'], 422, $this->message['custom_code'], $this->message['validation']);
    }
}
