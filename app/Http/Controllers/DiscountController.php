<?php

namespace App\Http\Controllers;

use App\Http\Requests\DiscountRequest;
use App\Http\Resources\DiscountResource;
use App\Http\Traits\CheckDiscountAvailabilityTrait;
use App\Http\Traits\RequestTrait;
use App\Models\Category;
use App\Models\Discount;
use App\Models\Item;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

use function PHPUnit\Framework\isNull;
use function Symfony\Component\String\s;

class DiscountController extends Controller
{
    use RequestTrait, CheckDiscountAvailabilityTrait;

    public function index(): JsonResponse
    {
        try {
            $discount = Discount::where('merchant_id', auth()->user()->id)
                ->orderBy('id', 'desc')
                ->get();

            /* $discount = $this->getDiscountCategories($discount);
            $discount = $this->getDiscountItems($discount);*/

            return $this->SuccessResponse(DiscountResource::collection($discount), 'Success', 200);
        } catch (ValidationException $exception) {
            $this->configureException($exception);
        } catch (\Exception $exception) {
            $this->writeErrors($exception);
        }

        return $this->FailResponse($this->message['data'], $this->message['messages'], $this->message['status'], $this->message['custom_code'], $this->message['validation']);
    }

    /*
     *
     * Create new Discount
     * */
    public function store(DiscountRequest $request): JsonResponse
    {
        try {

            $validated = $request->validated();
            $discount_schedules = $validated['discount_schedule'];
            foreach ($discount_schedules as $day => $options) {
                if ($options['is_open'] == 1) {
                    if (is_null($options['end_time']) || is_null($options['start_time'])) {
                        return $this->FailResponse([], ["provide start and end time of $day"], 422);
                    }
                }
                if ($options['is_open'] == 0) {
                    if (!is_null($options['end_time']) || !is_null($options['start_time'])) {
                        return $this->FailResponse([], ["$day is provided as closed. closed day should have start and end time."], 422);
                    }
                }
            }


            $checkAvailability = $this->isRunningDiscountAvailable($request->validated());

            if (array_key_exists('items', $checkAvailability) && $checkAvailability['items'] != null) {
                return $this->FailResponse($checkAvailability, ['Selected items already available for another Discount.'], 422);
            } else if (array_key_exists('categories', $checkAvailability) && $checkAvailability['categories'] != null) {
                return $this->FailResponse($checkAvailability, ['Selected categories already available for another Discount.'], 422);
            }

            $data = $this->prepareStoreData($request->validated());

            DB::beginTransaction();

            $discount = Discount::create($data);

            $items = $this->getDiscountIntoItem($request->validated());

            Item::whereIn('id', $items)
                ->update([
                    'discount_id' => $discount->id
                ]);

            DB::commit();

            return $this->SuccessResponse([], ['Discount created successfully'], 200);
        } catch (ValidationException $exception) {
            DB::rollBack();
            $this->configureException($exception);
        } catch (\Exception $exception) {
            DB::rollBack();
            $this->writeErrors($exception);
        }

        return $this->FailResponse($this->message['data'], $this->message['messages'], $this->message['status'], $this->message['custom_code'], $this->message['validation']);
    }

    /*
     *
     * Update Discount by discount ID
     * */
    public function update(DiscountRequest $request, $id): JsonResponse
    {
        try {

            $checkAvailability = $this->isRunningDiscountAvailable($request->validated(), $id);

            if (array_key_exists('items', $checkAvailability) && $checkAvailability['items'] != null) {
                return $this->FailResponse($checkAvailability, ['Selected items already available for another Discount.'], 422);
            } else if (array_key_exists('categories', $checkAvailability) && $checkAvailability['categories'] != null) {
                return $this->FailResponse($checkAvailability, ['Selected categories already available for another Discount.'], 422);
            }

            Log::info($request->validated());

            $data = $this->prepareStoreData($request->validated());

            DB::beginTransaction();

            $discount = Discount::where('id', $id)->first();

            if (!$discount) {
                return $this->SuccessResponse([], ['Invalid Discount id'], 422);
            }

            Discount::where('id', $id)->update($data);
            $items = $this->getDiscountIntoItem($request->validated());

            $this->removeItemDiscount($id);
            $this->setItemDiscount($items, $id);

            DB::commit();

            return $this->SuccessResponse([], ['Discount updated successfully'], 200);
        } catch (ValidationException $exception) {
            DB::rollBack();
            $this->configureException($exception);
        } catch (\Exception $exception) {
            DB::rollBack();
            $this->writeErrors($exception);
        }

        return $this->FailResponse($this->message['data'], $this->message['messages'], $this->message['status'], $this->message['custom_code'], $this->message['validation']);
    }

    /*
     *
     * Soft deleted a Discount
     * */
    public function delete($id): JsonResponse
    {
        try {
            $discount = Discount::where('id', $id)->delete();

            if (!$discount) {
                return $this->SuccessResponse([], ['Invalid Discount id'], 422);
            }
            return $this->SuccessResponse([], 'Discount successfully deleted.', 200);
        } catch (ValidationException $exception) {
            $this->configureException($exception);
        } catch (\Exception $exception) {
            $this->writeErrors($exception);
        }

        return $this->FailResponse($this->message['data'], $this->message['messages'], $this->message['status'], $this->message['custom_code'], $this->message['validation']);
    }

    /*
     *
     * Pre-pare discount Data for store and update
     * */
    public function prepareStoreData($data)
    {

        if (array_key_exists('discount_schedule', $data)) {
            $data['discount_schedule'] =  json_encode($data['discount_schedule']);
        }
        $data['categories'] =  json_encode($data['categories']);
        $data['items'] =  json_encode($data['items']);

        return $data;
    }
}
