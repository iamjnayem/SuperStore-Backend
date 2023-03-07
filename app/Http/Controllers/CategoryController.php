<?php

namespace App\Http\Controllers;

use App\Http\Traits\CategoryFilterTrait;
use App\Http\Traits\RequestTrait;
use App\Models\Category;
use App\Models\Item;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Bootstrap\LoadConfiguration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class CategoryController extends Controller
{
    use RequestTrait, CategoryFilterTrait;

    public function __construct(Request $request)
    {
        $this->setSettings($request);
    }

    public function store()
    {
        $rules = [
            'name' => 'required',
        ];
        try {
            $validation = Validator::make($this->request->all(), $rules);
            if ($validation->fails()) {
                throw new ValidationException($validation);
            }
            $arr = $this->request->only(
                [
                    'name',
                ]
            );
            $arr['user_id'] = auth()->user()->id;

            $alreadySameCategoryExists = Category::where('user_id', auth()->user()->id)->where('name', $arr['name'])->get();

            if ($alreadySameCategoryExists->count() >= 1) {
                return $this->FailResponse([], ['category name already exists'], 422);
            }


            $category = Category::create($arr);
            if ($category) {
                return $this->SuccessResponse($category);
            }
        } catch (ValidationException $exception) {
            $this->configureException($exception);
        } catch (\Exception $exception) {
            $this->writeErrors($exception);
        }
        return $this->FailResponse($this->message['data'], $this->message['messages'], $this->message['status'], $this->message['custom_code'], $this->message['validation']);
    }

    public function index()
    {
        try {
            $category = Category::with('merchantItems')
            ->withCount('merchantItems')
                ->where('user_id', auth()->user()->id)
                ->when(request()->query('search'), function ($q) {
                    $q->search();
                })
                ->when(request()->query('filter') == "recently", function ($q) {
                    $q->filterByRecentAdded();
                })
                ->when(request()->query('filter') == "topseller", function ($q) {
                    $q->filterBytopSeller();
                })
                ->paginate();

            if ($category) {
                return $this->SuccessResponse($category);
            }
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
            $category = Category::with('items')
                ->withCount('items')
                ->where(['user_id' => auth()->user()->id, 'id' => $id])
                ->first();
            if ($category == null) {
                return $this->FailResponse([], ["invalid category"], 422);
            }
            $category['top_seller_categories'] = $this->getTopSellersCategories();

            if ($category) {
                return $this->SuccessResponse($category);
            }
        } catch (ValidationException $exception) {
            $this->configureException($exception);
        } catch (\Exception $exception) {
            $this->writeErrors($exception);
        }
        return $this->FailResponse($this->message['data'], $this->message['messages'], $this->message['status'], $this->message['custom_code'], $this->message['validation']);
    }

    public function update($id)
    {
        $rules = [
            'name' => 'required',
        ];
        try {
            $validation = Validator::make($this->request->all(), $rules);
            if ($validation->fails()) {
                throw new ValidationException($validation);
            }
            $allCategories = Category::where('user_id', auth()->user()->id)->where('name', request('name'))->count();
            if($allCategories > 0){
                return $this->FailResponse([], [request('name'). " is already exists in your category list"], 422);
            }

            try {
                $category = Category::where(['user_id' => auth()->user()->id, 'id' => $id])->findOrFail($id);

            } catch (ModelNotFoundException $e) {
                return $this->FailResponse([], ["No such category to update"], 422);
            }

            if ($category && !$allCategories) {
                $updateCategory = $category->update(
                    ['name' => $this->request->name]
                );
                return $this->SuccessResponse($updateCategory);
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
            $itemsInCategory = Category::with('items')
                                ->where('user_id', auth()->user()->id)
                                ->where('id', $id)
                                ->get();
            if(count($itemsInCategory) == 0){
                $this->message['messages'] = ['Category not found or already deleted'];
                $this->message['status'] = 422;
                return $this->FailResponse($this->message['data'], $this->message['messages'], $this->message['status'], $this->message['custom_code'], $this->message['validation']);
            }

            $numberOfItems = $itemsInCategory[0]->items->count();

            if($numberOfItems){
                $this->message['messages'] = ['Categories having items cannot be deleted'];
                $this->message['status'] = 422;
                return $this->FailResponse($this->message['data'], $this->message['messages'], $this->message['status'], $this->message['custom_code'], $this->message['validation']);
            }

            $category = Category::where('user_id', auth()->user()->id)
                    ->where('id', $id)
                    ->delete();

            if ($category) {
                return $this->SuccessResponse();
            }

        } catch (ValidationException $exception) {
            $this->configureException($exception);
        } catch (\Exception $exception) {
            $this->writeErrors($exception);
        }
        return $this->FailResponse($this->message['data'], $this->message['messages'], $this->message['status'], $this->message['custom_code'], $this->message['validation']);
    }

    public function getAllCategory()
    {
        try {
            $category = Category::select('id','name')
                ->where('user_id', auth()->user()->id)->get();

            if ($category) {
                return $this->SuccessResponse($category);
            }
        } catch (ValidationException $exception) {
            $this->configureException($exception);
        } catch (\Exception $exception) {
            $this->writeErrors($exception);
        }
        return $this->FailResponse($this->message['data'], $this->message['messages'], $this->message['status'], $this->message['custom_code'], $this->message['validation']);
    }
}
