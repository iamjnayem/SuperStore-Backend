<?php

namespace App\Http\Controllers;
use App\Http\Traits\InvoiceTrait;
use App\Http\Traits\InvoiceTransactionTrait;
use App\Http\Traits\RequestTrait;
use App\Models\CategoryWiseTotalReport;
use App\Models\Invoice;
use App\Models\InvoiceTransaction;
use App\Models\InvoiceTransactionCategoryWise;
use App\Models\InvoiceTransactionItemWise;
use App\Models\ItemWiseTotalReport;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class InvoiceTransactionController extends Controller
{
    use RequestTrait,InvoiceTrait,InvoiceTransactionTrait;

    public function __construct(Request $request)
    {
        $this->setSettings($request);
    }

    public function store()
    {
        $rules = [
            'user_id' => 'required',
            'invoice_id' => 'required',
            'deshi_transaction_id' => 'required',
        ];
        try {
            $validation = Validator::make($this->request->all(), $rules);
            if ($validation->fails()) {
                throw new ValidationException($validation);
            }
            $arr = $this->request->only(
                [
                    'user_id',
                    'invoice_id',
                    'deshi_transaction_id',
                ]
            );

            $invoiceTransaction = InvoiceTransaction::where('invoice_id',$arr['invoice_id'])->first();
            if ($invoiceTransaction){
                return $this->FailResponse('This Invoice already has a transaction');
            }else{
                DB::transaction(function () use ($arr){
                    $invoice = Invoice::where(['user_id' => $arr['user_id'],'id' =>$arr['invoice_id']])->with(['merchant','user','invoice_items.item'])->first();
                    //$invoice_mapped = $this->get_invoice($invoice);
                    $transactionId = uniqid();

                    $creditInfo = $this->creditAccount($invoice,$transactionId,$arr['deshi_transaction_id']);
                    $debitInfo = $this->debitAccount($invoice,$transactionId,$arr['deshi_transaction_id']);

                    $itemsInfo= $this->itemInformation($invoice);
                    $categoryInfo= $this->categoryInformation($invoice);

                    $creditTransaction = InvoiceTransaction::create($creditInfo);
                    $debitTransaction = InvoiceTransaction::create($debitInfo);

                    foreach ($itemsInfo as $i => $item){
                        $item['invoice_transaction_id'] = $debitTransaction->transaction_id;
                        InvoiceTransactionItemWise::create($item);

                        $prevAmount = ItemWiseTotalReport::where('item_id',$item['item_id'])->first();
                        $getPrevAmount = $prevAmount ? $prevAmount->total_sale_amount : 0 ;
                        $getPrevAmountWithoutDiscount = $prevAmount ? $prevAmount->total_sale_amount_without_discount : 0 ;
                        $getPrevQuantity = $prevAmount ? $prevAmount->sold_quantity : 0 ;

                        DB::table('item_wise_total_reports')
                            ->updateOrInsert(
                                ['item_id' => $item['item_id']],
                                [
                                    'total_sale_amount' => $getPrevAmount + $item['total_price'],
                                    'total_sale_amount_without_discount' => $getPrevAmountWithoutDiscount + $item['total_price_without_discount'],
                                    'sold_quantity' => $getPrevQuantity + $item['quantity'],
                                ]
                            );
                    }

                    foreach ($categoryInfo as $i => $category){
                        $category['invoice_transaction_id'] = $debitTransaction->transaction_id;
                        InvoiceTransactionCategoryWise::create($category);

                        $prevAmount = CategoryWiseTotalReport::where('category_id',$category['category_id'])->first();
                        $getPrevAmount = $prevAmount ? $prevAmount->total_sale_amount : 0 ;
                        $getPrevAmountWithoutDiscount = $prevAmount ? $prevAmount->total_sale_amount_without_discount : 0 ;
                        $getPrevQuantity = $prevAmount ? $prevAmount->sold_quantity : 0 ;

                        DB::table('category_wise_total_reports')
                            ->updateOrInsert(
                                ['category_id' => $category['category_id']],
                                [
                                    'total_sale_amount' => $getPrevAmount + $category['total_price'],
                                    'total_sale_amount_without_discount' => $getPrevAmountWithoutDiscount + $item['total_price_without_discount'],
                                    'sold_quantity' => $getPrevQuantity + $item['quantity'],
                                ]
                            );
                    }
                });
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
