<?php

namespace App\Http\Controllers;

use App\Http\Resources\InvoiceListResource;
use App\Http\Resources\InvoiceResource;
use App\Http\Traits\InvoiceTrait;
use App\Http\Traits\RequestTrait;
use App\Models\Connect;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InvoiceTransaction;
use App\Models\Item;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class InvoiceController extends Controller
{
    use RequestTrait, InvoiceTrait;

    private $invoice = false;

    public function __construct(Request $request)
    {
        $this->setSettings($request);
    }

    public function get_all_status()
    {
        return $this->SuccessResponse($this->getAllStatues());
    }

    public function store()
    {
        $rules = [
            'connect_id' => 'required|exists:connects,id',
            'title' => 'nullable',
            'notes' => 'nullable',
            'items' => 'required|array',
            'items.*.id' => 'required|exists:items,id',
            'items.*.quantity' => 'required',
            'total_bill_amount' => 'required',
            'final_bill_amount' => 'required',
            'discount_amount' => 'required',
            'delivery_charge_amount' => 'required',
            'vat_tax_amount' => 'required',
            'notification_method' => 'required|array',
            'expire_after' => 'required',
            'due_date' => 'required|date_format:Y-m-d',
            'schedule_at_date' => 'nullable|date_format:Y-m-d',
            'schedule_at_time' => 'nullable|date_format:H:i:s',
        ];
        
        try {
            $validation = Validator::make($this->request->all(), $rules);
            if ($validation->fails()) {
                throw new ValidationException($validation);
            }
                        
            $arr = $this->get_fillables();

            $validItem = true;
            foreach ($this->request->items as $item){
                $getItem = Item::where(['user_id'=>auth()->user()->id, 'id'=>$item['id']])->first();
                $validItem = $getItem ? true : false;
                if ($validItem == false) break;
            }
            if ($validItem){
                DB::transaction(function () use ($arr){
                    $invoice = Invoice::create($arr);
                    foreach ($this->request->items as $item){
                        InvoiceItem::create([
                            'invoice_id' => $invoice->id,
                            'item_id' => $item['id'],
                            'quantity' => $item['quantity'],
                        ]);
                    }
                    $this->invoice = $invoice;
                });
            }
            else{
                return $this->FailResponse($this->message['data'],['invalid item'],422);
            }

            if($this->invoice){
                return $this->SuccessResponse(['id' => $this->invoice->id]);
            }
        }catch (ValidationException $exception){
            $this->configureException($exception);
        }catch (\Exception $exception){
            $this->writeErrors($exception);
        }
        return $this->FailResponse($this->message['data'],$this->message['messages'],$this->message['status'],$this->message['custom_code'],$this->message['validation']);
    }

    private function get_fillables()
    {
        $connect = Connect::where('connector_user_id',auth()->user()->id)->where('id',$this->request->connect_id)->first();
        if(!$connect){
            throw ValidationException::withMessages(['Invalid connect id']);
        }
        $arr = $this->request->only([
            'connect_id',
            'custom_invoice_id',
            'title',
            'total_bill_amount',
            'final_bill_amount',
            'discount_amount',
            'delivery_charge_amount',
            'vat_tax_amount',
            'expire_after',
            'reminder',
            'notes',
            'schedule_at_date',
            'schedule_at_time',
            'due_date',
        ]);
        $arr['notification_method'] = implode(',',$this->request->notification_method);
        $arr['merchant_user_id'] = auth()->user()->id;
        $arr['invoice_id'] = $this->generateRandomString(true, true, false, '', 10);
        $arr['user_id'] = $connect->user_id;

        return $arr;
    }


    function generateRandomString($alpha = true, $nums = true, $usetime = false, $string = '', $length = 120) {
        $alpha = ($alpha == true) ? 'abcdefghijklmnopqrstuvwxyz' : '';
        $nums = ($nums == true) ? '1234567890' : '';

        if ($alpha == true || $nums == true || !empty($string)) {
            if ($alpha == true) {
                $alpha = $alpha;
                $alpha .= strtoupper($alpha);
            }
        }
        $randomstring = '';
        $totallength = $length;
        for ($na = 0; $na < $totallength; $na++) {
            $var = (bool)rand(0,1);
            if ($var == 1 && $alpha == true) {
                $randomstring .= $alpha[(rand() % mb_strlen($alpha))];
            } else {
                $randomstring .= $nums[(rand() % mb_strlen($nums))];
            }
        }
        if ($usetime == true) {
            $randomstring = $randomstring.time();
        }
        return($randomstring);
    }


    public function index()
    {
        try {
            $invoice = Invoice::when(request()->query('search'), function ($q) {
                    $q->search();
                })
                ->orWhere(function ($query){
                    $query->with('user')->whereHas('user', function ($q){
                        $q->search();
                    });
                })
                ->when(request()->query('starred'), function ($q) {
                    $q->filterByStarred();
                })
                ->when(request()->query('filter'), function ($q) {
                    $q->filterByMerchantStatus();
                })
                ->where(['merchant_user_id' => auth()->user()->id])
                ->orderBy('id','desc')
                ->paginate();
            return $this->SuccessResponse(InvoiceListResource::collection($invoice));

        }catch (ValidationException $exception){
            $this->configureException($exception);
        }catch (\Exception $exception){
            $this->writeErrors($exception);
        }
        return $this->FailResponse($this->message['data'],$this->message['messages'],$this->message['status'],$this->message['custom_code'],$this->message['validation']);
    }

    public function show($id)
    {
        try {
            
            $invoiceFound = false;

            $transactionId = InvoiceTransaction::where('user_id',auth()->user()->id)
                ->where('invoice_id',$id)
                ->first();
            $invoice = Invoice::where(['merchant_user_id' => auth()->user()->id,'id' => $id])->with(['user','merchant','invoice_items.item'])->first();
            if($invoice){
                $invoiceFound = true;
            }

            $invoice['transaction_id'] = $transactionId;

            if($invoiceFound){
                return $this->SuccessResponse(new InvoiceResource($invoice));
            }else{
                $this->message['messages'] = ["invalid invoice id"];
                $this->message['status'] = 422;
            }

        }catch (ValidationException $exception){
            $this->configureException($exception);
        }catch (\Exception $exception){
            $this->writeErrors($exception);
        }
        return $this->FailResponse($this->message['data'],$this->message['messages'],$this->message['status'],$this->message['custom_code'],$this->message['validation']);
    }


    public function delete($id)
    {
        try {
            $invoice = Invoice::where(['merchant_user_id' => auth()->user()->id,'id' => $id])->delete();
            if($invoice){
                return $this->SuccessResponse();
            }
            $this->message['messages'] = 'Category not found or already deleted';
            $this->message['status'] = 400;
            return $this->FailResponse([], [$this->message['messages']], $this->message['status']);
        }catch (ValidationException $exception){
            $this->configureException($exception);
        }catch (\Exception $exception){
            $this->writeErrors($exception);
        }
        return $this->FailResponse($this->message['data'],$this->message['messages'],$this->message['status'],$this->message['custom_code'],$this->message['validation']);
    }

    public function markInvoiceToStarred($id)
    {
        try {
            $invoice = Invoice::where(['merchant_user_id' => auth()->user()->id,'id' => $id])->update([
                'is_starred' => 1
            ]);
            if ($invoice){
                return $this->SuccessResponse();
            }else{
                return $this->FailResponse([], ['invalid invoice id'], 400);
            }
        }catch (ValidationException $exception){
            $this->configureException($exception);
        }catch (\Exception $exception){
            $this->writeErrors($exception);
        }
        return $this->FailResponse($this->message['data'],$this->message['messages'],$this->message['status'],$this->message['custom_code'],$this->message['validation']);
    }

    public function unmarkInvoiceFromStarred($id)
    {
        try {
            $invoice = Invoice::where(['merchant_user_id' => auth()->user()->id,'id' => $id])->update([
                'is_starred' => 0
            ]);
            if ($invoice){
                return $this->SuccessResponse();
            }else{
                return $this->FailResponse([], ["invalid invoice id"], 400);
            }
        }catch (ValidationException $exception){
            $this->configureException($exception);
        }catch (\Exception $exception){
            $this->writeErrors($exception);
        }
        return $this->FailResponse($this->message['data'],$this->message['messages'],$this->message['status'],$this->message['custom_code'],$this->message['validation']);
    }

    public function status_change($id)
    {
        $rules = [
            'status' => 'required',
        ];
        try {
            $validation = Validator::make($this->request->all(), $rules);
            if ($validation->fails()) {
                throw new ValidationException($validation);
            }
            if(!array_key_exists($this->request->status,$this->statuses)){
                throw ValidationException::withMessages(['Invalid status']);
            }
            if(Invoice::where(['id' => $id])->update(['status' => $this->request->status])){
                $invoice = Invoice::where(['id' => $id])->with(['merchant','user','invoice_items.item'])->first();
                $invoice_mapped = $this->get_invoice($invoice);
                if($invoice){
                    return $this->SuccessResponse($invoice_mapped);
                }
            }else{
                return $this->FailResponse([], ["invalid invoice id"], 400);
            }

        }catch (ValidationException $exception){
            $this->configureException($exception);
        }catch (\Exception $exception){
            $this->writeErrors($exception);
        }
        return $this->FailResponse($this->message['data'],$this->message['messages'],$this->message['status'],$this->message['custom_code'],$this->message['validation']);
    }

    public function get_invoice($invoice)
    {
        return [
            'id' => $invoice->id,
            'invoice_id' => $invoice->invoice_id,
            'custom_invoice_id' => $invoice->invoice_id,
            'title' => $invoice->title,
            'total_bill_amount' => $invoice->total_bill_amount,
            'final_bill_amount' => $invoice->final_bill_amount,
            'discount_amount' => $invoice->discount_amount,
            'delivery_charge_amount' => $invoice->delivery_charge_amount,
            'vat_tax_amount' => $invoice->vat_tax_amount,
            'expire_after' => $invoice->expire_after,
            'reminder' => $invoice->reminder,
            'notes' => $invoice->notes,
            'original_status' => $invoice->status,
            'status' => $this->getStatus($invoice->status),
            'status_alias' => $this->getStatusAliasName($invoice->status),
            'head' => "Invoice " . $this->getStatus($invoice->status),
            'schedule_at_date' => $invoice->schedule_at_date,
            'schedule_at_time' => $invoice->schedule_at_time,
            'created_at_date' => Carbon::createFromFormat('Y-m-d H:i:s', $invoice->created_at)->format('Y-m-d'),
            'created_at_time' => Carbon::createFromFormat('Y-m-d H:i:s', $invoice->created_at)->format('H:i:s'),
            'notification_method' => explode(',',$invoice->notification_method),
            'merchant' => [
                'id' => $invoice->merchant->id,
                'name' => $invoice->merchant->name,
                'avatar' => $invoice->merchant->avatar,
                'mobile_no' => $invoice->merchant->mobile_no,
                'user_type' => $invoice->merchant->user_type,
                'address' => $invoice->merchant->address,
                'email' => $invoice->merchant->email,
            ],
            'user' => [
                'id' => $invoice->user->id,
                'name' => $invoice->user->name,
                'avatar' => $invoice->user->avatar,
                'mobile_no' => $invoice->user->mobile_no,
                'user_type' => $invoice->user->user_type,
                'address' => $invoice->user->address,
                'email' => $invoice->user->email,
            ],
            'items' => $invoice->invoice_items->map(function($item){
                return [
                    'name' => $item->item->name,
                    'unit' => $item->item->unit,
                    'unit_price' => $item->item->unit_price,
                    'quantity' => $item->quantity,
                    'image' => $item->item->image
                ];
            }),
        ];
    }

    public function get_invoice_for_payment($id)
    {
        try {
            $invoice = Invoice::where(['id' => $id])->with(['user','merchant'])->first();
            $invoice_mapped = [
                'id' => $invoice->id,
                'invoice_id' => $invoice->invoice_id,
                'custom_invoice_id' => $invoice->invoice_id,
                'title' => $invoice->title,
                'total_bill_amount' => $invoice->total_bill_amount,
                'final_bill_amount' => $invoice->final_bill_amount,
                'discount_amount' => $invoice->discount_amount,
                'delivery_charge_amount' => $invoice->delivery_charge_amount,
                'vat_tax_amount' => $invoice->vat_tax_amount,
                'expire_after' => $invoice->expire_after,
                'reminder' => $invoice->reminder,
                'notes' => $invoice->notes,
                'is_starred' => $invoice->is_starred,
                'original_status' => $invoice->status,
                'status' => $this->getStatus($invoice->status),
                'status_alias' => $this->getStatusAliasName($invoice->status,'merchant'),
                'head' => "Bill " . $this->getStatus($invoice->status),
                'schedule_at_date' => $invoice->schedule_at_date,
                'schedule_at_time' => $invoice->schedule_at_time,
                'created_at_date' => Carbon::createFromFormat('Y-m-d H:i:s', $invoice->created_at)->format('Y-m-d'),
                'created_at_time' => Carbon::createFromFormat('Y-m-d H:i:s', $invoice->created_at)->format('H:i:s'),
                'notification_method' => explode(',',$invoice->notification_method),
                'user' => [
                    'id' => $invoice->user->id,
                    'name' => $invoice->user->name,
                    'avatar' => $invoice->user->avatar,
                    'mobile_no' => $invoice->user->mobile_no,
                    'user_type' => $invoice->user->user_type,
                    'address' => $invoice->user->address,
                    'email' => $invoice->user->email,
                ],
                'merchant' => [
                    'id' => $invoice->merchant->id,
                    'name' => $invoice->merchant->name,
                    'avatar' => $invoice->merchant->avatar,
                    'mobile_no' => $invoice->merchant->mobile_no,
                    'user_type' => $invoice->merchant->user_type,
                    'address' => $invoice->merchant->address,
                    'email' => $invoice->merchant->email,
                ],
            ];
            if($invoice){
                return $this->SuccessResponse($invoice_mapped);
            }
        }catch (ValidationException $exception){
            $this->configureException($exception);
        }catch (\Exception $exception){
            $this->writeErrors($exception);
        }
        return $this->FailResponse($this->message['data'],$this->message['messages'],$this->message['status'],$this->message['custom_code'],$this->message['validation']);
    }

}
