<?php

namespace App\Http\Controllers;

use App\Http\Resources\InvoiceListResource;
use App\Http\Traits\RequestTrait;
use App\Models\Category;
use App\Models\Connect;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Bootstrap\LoadConfiguration;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ConnectController extends Controller
{
    use RequestTrait;

    public bool $isCreated = false;
    public  $connectDetails = null;
    public  $isConnectExist = null;
    public $newUser = null;

    public function __construct(Request $request)
    {
        $this->setSettings($request);
    }

    public function store()
    {
        $rules = [
            'name' => 'required',
            'mobile_no' => 'required',
            'deshi_user_id' => 'required',
            'user_type' => 'required',
            'email' => 'nullable',
            'avatar' => 'nullable',
            'business_category_id' => 'required_if:user_type,=,merchant',
        ];

        try {
            //sanitize mobile number
            if ($this->request->has('mobile_no')) {
                $this->request->mobile_no = Str::startsWith($this->request->mobile_no, '+880') ? $this->request->mobile_no : '+88' . $this->request->mobile_no;
            }

            //self connect check
            if (auth()->user()->mobile_no == $this->request->mobile_no) {
                return $this->FailResponse([], ["Your mobile number and connector mobile number must be different"], 422);
            }

            //validate
            $validation = Validator::make($this->request->all(), $rules);
            if ($validation->fails()) {
                return $this->FailResponse([], [$validation->errors()->first()], 422);
            }


            //take necessary info to create connect
            $arr = $this->request->only(
                [
                    'name',
                    'mobile_no',
                    'deshi_user_id',
                    'user_type',
                    'email',
                    'avatar',
                    'business_category_id',
                ]
            );


            $user = User::where(['mobile_no' => $this->request->mobile_no])->first();

            Log::info($user);

            //user exists
            if($user){
                //check connect
                $connectDetails = Connect::where(['connector_user_id' => auth()->user()->id, 'user_id' => $user->id])->first();

                if($connectDetails){
                    return $this->FailResponse([], ['This user is already in your connect list'], 422);
                }else{
                    // else create connect
                    $connectDetails = Connect::create(['connector_user_id' => auth()->user()->id, 'user_id' => $user->id]);
                    return $this->SuccessResponse(['id' => $connectDetails->id], ['connect created successfully'], 200);
                }

            }
            else{
                //create user
                $user = User::create($arr);
                //create connect
                $connectDetails = Connect::create(['connector_user_id' => auth()->user()->id, 'user_id' => $user->id]);
                return $this->SuccessResponse(['id' => $connectDetails->id], ['connect created successfully'], 200);
            }

        } catch (ValidationException $exception) {
            $this->configureException($exception);
            $this->writeErrors($exception);
        } catch (\Exception $exception) {
            $this->writeErrors($exception);
        }
        return $this->FailResponse($this->message['data'], $this->message['messages'], $this->message['status'], $this->message['custom_code'], $this->message['validation']);
    }

    public function index()
    {
        try {
            $connect = Connect::with('user')
                ->when(request()->query('search'), function ($query) {
                    $query->with('user')->whereHas('user', function ($q) {
                        $q->search();
                    });
                })
                ->when(request()->query('filter'), function ($query) {
                    $query->with('user')->whereHas('user', function ($q) {
                        $q->filterByUserType();
                    });
                })
                ->when(request()->query('favorite'), function ($q) {
                    $q->filterByFavorite();
                })
                ->when(request()->query('sortby') == 'alphabetical', function ($query) {
                    $query->with('user')->whereHas('user', function ($q) {
                        $q->sortByAlphabetical();
                    });
                })
                ->when(request()->query('sortby') == 'recently', function ($q) {
                    $q->sortByRecently();
                })
                ->when(request()->query('sortby') == 'topseller', function ($q) {
                    $q->sortByTopseller();
                })
                ->where(['connector_user_id' => auth()->user()->id])
                ->paginate();

            if ($connect) {
                return $this->SuccessResponse($connect);
            }
        } catch (ValidationException $exception) {
            $this->configureException($exception);
        } catch (\Exception $exception) {
            $this->writeErrors($exception);
        }
        return $this->FailResponse($this->message['data'], $this->message['messages'], $this->message['status'], $this->message['custom_code'], $this->message['validation']);
    }

    public function connectCount(): JsonResponse
    {
        try {
            $connectCount = Connect::with('user')
                ->where(['connector_user_id' => auth()->user()->id])
                ->get();

            $grouped = $connectCount->groupBy('user.user_type');
            $groupUser = $grouped->all();
            $data = [];

            foreach ($groupUser as $i => $user) {
                $data[$i]['user_type'] = $user[0]->user->user_type;
                $data[$i]['count'] = sizeof($user);
            }
            return $this->SuccessResponse(array_values($data));
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
            $connect = Connect::with('user')
                ->with('user_settings:user_id,is_email_visible,is_address_visible')
                ->where(['connector_user_id' => auth()->user()->id, 'id' => $id])
                ->first();

            if ($connect) {
                $amountReceived = Invoice::where(['user_id' => $connect['user_id'], 'connect_id' => $id, 'status' => 9])->sum('final_bill_amount');
                $amountReceivable = Invoice::where(['user_id' => $connect['user_id'], 'connect_id' => $id, 'status' => 2])->sum('final_bill_amount');

                $connect['receivable'] = [
                    'total_received' => $amountReceived,
                    'total_pending' => $amountReceivable,
                ];

                $invoice = Invoice::where(['merchant_user_id' => auth()->user()->id,'user_id' => $connect->user_id])
                    ->orderBy('id','desc')
                    ->limit(5)
                    ->get();

                $connect['recent_invoices'] = InvoiceListResource::collection($invoice);



                if ($connect['user']['user_type'] == 'merchant') {

                    $amountPaid = Invoice::where(['user_id' => auth()->user()->id, 'merchant_user_id' => $connect['user_id'], 'status' => 9])
                        ->sum('final_bill_amount');

                    $amountDue = Invoice::where(['user_id' => auth()->user()->id, 'merchant_user_id' => $connect['user_id'], 'status' => 2])
                        ->sum('final_bill_amount');

                    $connect['payable'] = [
                        'total_paid' => $amountPaid,
                        'total_due' => $amountDue,
                    ];
                }

                return $this->SuccessResponse($connect);
            }
            else{
                return $this->FailResponse([], ["connect id not found"], 422);
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
            $category = Connect::where(['connector_user_id' => auth()->user()->id, 'id' => $id])->delete();
            if ($category) {
                return $this->SuccessResponse();
            }
            $this->message['messages'] = 'Category not found or already deleted';
            $this->message['status'] = 400;
        } catch (ValidationException $exception) {
            $this->configureException($exception);
        } catch (\Exception $exception) {
            $this->writeErrors($exception);
        }
        return $this->FailResponse($this->message['data'], $this->message['messages'], $this->message['status'], $this->message['custom_code'], $this->message['validation']);
    }

    public function addConnectToFavorite($id)
    {
        try {
            $connect = Connect::where(['connector_user_id' => auth()->user()->id, 'id' => $id])->update([
                'is_favourite' => 1
            ]);
            if ($connect) {
                return $this->SuccessResponse($connect);
            }
            else{
                return $this->FailResponse([], ["connect id not found"], 422);
            }
        } catch (ValidationException $exception) {
            $this->configureException($exception);
        } catch (\Exception $exception) {
            $this->writeErrors($exception);
        }
        return $this->FailResponse($this->message['data'], $this->message['messages'], $this->message['status'], $this->message['custom_code'], $this->message['validation']);
    }

    public function removeConnectFromFavorite($id)
    {
        try {
            $connect = Connect::where(['connector_user_id' => auth()->user()->id, 'id' => $id])->update([
                'is_favourite' => 0
            ]);
            if ($connect) {
                return $this->SuccessResponse($connect);
            }else{
                return $this->FailResponse([], ["connect id not found"], 422);
            }
        } catch (ValidationException $exception) {
            $this->configureException($exception);
        } catch (\Exception $exception) {
            $this->writeErrors($exception);
        }
        return $this->FailResponse($this->message['data'], $this->message['messages'], $this->message['status'], $this->message['custom_code'], $this->message['validation']);
    }
}
