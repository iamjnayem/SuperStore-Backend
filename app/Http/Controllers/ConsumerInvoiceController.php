<?php

namespace App\Http\Controllers;

use App\Http\Traits\InvoiceTrait;
use App\Http\Traits\RequestTrait;
use App\Models\Invoice;
use App\Models\Item;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;

class ConsumerInvoiceController extends Controller
{
    use RequestTrait, InvoiceTrait;

    private $invoice = false;

    public function __construct(Request $request)
    {
        $this->setSettings($request);
    }

    public function index()
    {
        try {
            $invoice = Invoice::when(request()->query('search'), function ($q) {
                    $q->search();
                })
                ->orWhere(function ($query){
                    $query->with('merchant')->whereHas('merchant', function ($q){
                        $q->search();
                    });
                })
                ->when(request()->query('filter'), function ($q) {
                    $q->filterByConsumerStatus();
                })
                ->where(['user_id' => auth()->user()->id])
                ->orderBy('id','desc')
                ->paginate()
                ->through(function ($invoice){
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
                    'status' => $this->getStatus($invoice->status),
                    'status_alias' => $this->getStatusAliasName($invoice->status),
                    'head' => "Bill " . $this->getStatus($invoice->status),
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
                    ]
                ];
            });
            if($invoice){
                return $this->SuccessResponse($invoice);
            }
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
            $invoice = Invoice::where(['user_id' => auth()->user()->id,'id' => $id])->with(['merchant','user','invoice_items.item'])->first();
            if($invoice){
                $invoice_mapped = $this->get_invoice($invoice);
                return $this->SuccessResponse($invoice_mapped);
            }else{
                return $this->FailResponse([], ["invalid invoice id"], 422);
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
                    'id' => $item->item->id,
                    'name' => $item->item->name,
                    'category_id' => $item->item->category_id,
                    'unit' => $item->item->unit,
                    'unit_price' => $item->item->unit_price,
                    'quantity' => $item->quantity,
                    'image' => $item->item->image
                ];
            }),
        ];
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
            $invoice = Invoice::where(['user_id' => auth()->user()->id, 'id' => $id])->first();
            if(!$invoice){
                return $this->FailResponse([], ["invalid invoice id"], 422);
            }

            $invoice = Invoice::where(['user_id' => auth()->user()->id,'id' => $id])->update(['status' => $this->request->status]);
            $invoice = Invoice::where(['user_id' => auth()->user()->id,'id' => $id])->with(['merchant','user','invoice_items.item'])->first();
            $invoice_mapped = $this->get_invoice($invoice);
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
