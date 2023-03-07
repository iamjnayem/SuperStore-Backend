<?php

namespace App\Http\Controllers\Kam;

use App\Http\Controllers\Controller;
use App\Http\Requests\Kam\ItemUpdateRequest;
use App\Http\Resources\KAM\ProductResource;
use App\Models\Item;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProductController extends Controller
{


    public function index(Request $request)
    {
        $query = Item::query();

        $query->select('id', 'user_id', 'category_id', 'name', 'description',
            'unit', 'unit_price', 'image', 'stock_quantity', 'addons', 'is_publish',
            'discount_id', 'status', 'created_at', 'updated_at',
        );

        $query->with(
            [
                'user:id,name,mobile_no,deshi_user_id,avatar',
                'category:id,name',
                'discount:id,is_percentage,amount',
            ]
        );

        $query->where('is_publish', '!=', 0);

        $query->when($request->is_publish, function ($query, $type) {
            $query->where('is_publish', $type);
        });



        $query->when($request->filter, function ($query) {
            $users = User::whereIn('deshi_user_id', request('users', []))->pluck('id');
            $query->whereIn('user_id', $users);
        });



        $query->when($request->str, function ($query, $str) {
            $query->where('name', 'like', "%$str%")
                ->orWhere('unit_price', "$str$");
        });



        $query->orderBy('user_id');
        $query->orderBy('name');

        $perPage = $request->per_page ?: 25;

        return ProductResource::collection($query->paginate($perPage));
    }



    public function show(Item $item)
    {
        return new ProductResource($item);
    }





    public function update(ItemUpdateRequest $request)
    {
        try {
            $item = Item::find($request->product_id);
            $item->is_publish = $request->status;
            $item->save();

            return $this->SuccessResponse(new ProductResource($item));

        }catch (\Exception $exception){
            $this->writeErrors($exception);
            return $this->FailResponse([], ['Failed', $exception->getMessage()]);
        }

    }


}
