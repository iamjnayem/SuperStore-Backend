<?php

namespace App\Http\Controllers;

use App\Http\Traits\RequestTrait;
use App\Models\Category;
use App\Models\CategoryWiseTotalReport;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InvoiceTransaction;
use App\Models\InvoiceTransactionCategoryWise;
use App\Models\InvoiceTransactionItemWise;
use App\Models\Item;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;

class MerchantReportController extends Controller
{
    use RequestTrait;

    public function __construct(Request $request)
    {
        $this->setSettings($request);
    }

    public function salesReportItemWise()
    {
        try {
            $transactionIds = InvoiceTransaction::when(request()->query('date_from') && request()->query('date_to'), function ($q){
                    $q->filterByDate();
                })
                ->where(['user_id' => auth()->user()->id, 'type'=>'credit'])
                ->pluck('transaction_id');

            $totalSales = InvoiceTransactionItemWise::whereIn('invoice_transaction_id',$transactionIds)->sum('total_price_without_discount');

            if (sizeof($transactionIds)){
                $getSaleItems = InvoiceTransactionItemWise::with('item:id,name')->whereIn('invoice_transaction_id',$transactionIds)
                    ->select('item_id',
                        DB::raw('sum(total_price_without_discount) as total_sale'),
                        DB::raw('sum(total_price_without_discount)*100/'.$totalSales.'as percentage')
                    )
                    ->groupBy('item_id')->limit(15)->get();
            }else{
                $getSaleItems = [];
            }

            $data = [
                'total_sales' => $totalSales,
                'item_wise_sale'=>$getSaleItems
            ];

            return $this->SuccessResponse($data);

        }catch (ValidationException $exception){
            $this->configureException($exception);
        }catch (\Exception $exception){
            $this->writeErrors($exception);
        }
        return $this->FailResponse($this->message['data'],$this->message['messages'],$this->message['status'],$this->message['custom_code'],$this->message['validation']);

    }

    public function salesReportCategoryWise($id)
    {
        try {
            $getAuthUserItemId = Item::where('category_id',$id)->pluck('id');
            

            $getSalesCategoryItemId = InvoiceTransactionItemWise::whereIn('item_id',$getAuthUserItemId)->pluck('item_id');
            
            $getItem = Item::whereIn('id',$getSalesCategoryItemId)->paginate();

            $checkValidCategory = Category::where(['user_id'=>auth()->user()->id, 'id'=>$id])->first();
            if ($checkValidCategory){
                $totalSales = CategoryWiseTotalReport::where(['category_id'=>$id])->value('total_sale_amount_without_discount');
                $totalSales = $totalSales ? $totalSales : 0;

                $data = [
                    'category' => $checkValidCategory['name'],
                    'total_sales' => $totalSales,
                    'sales_items' => $getItem,
                ];

                return $this->SuccessResponse($data);
            }
            else return $this->FailResponse([], ['Invalid Category'], 422);

        }catch (ValidationException $exception){
            $this->configureException($exception);
        }catch (\Exception $exception){
            $this->writeErrors($exception);
        }
        return $this->FailResponse($this->message['data'],$this->message['messages'],$this->message['status'],$this->message['custom_code'],$this->message['validation']);

    }
}
