<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ConnectController;
use App\Http\Controllers\ConsumerInvoiceController;
use App\Http\Controllers\CreateUserController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DiscountController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\InvoiceTransactionController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\Kam\KamItemController;
use App\Http\Controllers\Merchant\MarketPlaceController;
use App\Http\Controllers\Merchant\OrderManagementController;
use App\Http\Controllers\MerchantReportController;
use App\Http\Controllers\MerchantSalesReportController;
use App\Http\Controllers\Personal\OrderController;
use App\Http\Controllers\PrivacySettingController;
use App\Http\Controllers\StoreSettingsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

/*Route::get('/artisan', function () {
    $output = Artisan::call('migrate');
    echo "<pre>$output</pre>";
});*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    \Carbon\Carbon::parse($request->date)->format('Y-m-d');
    return $request->user();
});
Route::middleware('core')->prefix('/v1')->group(function (){
    Route::get('/get-token/{deshi_user_id}',[AuthController::class,'get_token_by_deshi']);
    Route::get('/check-user-exists/{deshi_user_id}',[AuthController::class,'check_user_has_account_by_deshi']);
    Route::get('/user-details/{id}',[AuthController::class,'check_user_has_account_by_id']);
    Route::post('/create-user',[CreateUserController::class,'create_user_by_deshi']);
    Route::get('invoice-detail/{id}', [InvoiceController::class, 'get_invoice_for_payment']);
    Route::post('/invoice-status-update-by-deshi/{id}', [InvoiceController::class, 'status_change']);
    //Invoice Transactions
    Route::post('invoice-transaction', [InvoiceTransactionController::class, 'store']);
    Route::get('get-user-settings/{id}', [AuthController::class, 'getUserSettings']);
    //Invoice Merchant sales report
    Route::get('invoice-sales-report/{id}', [MerchantSalesReportController::class, 'salesReport']);


    Route::any('kam/shop/item', [\App\Http\Controllers\Kam\ProductController::class, 'index']);
    Route::get('kam/shop/item/{item}', [\App\Http\Controllers\Kam\ProductController::class, 'show']);
    Route::post('kam/shop/item/update-status', [\App\Http\Controllers\Kam\ProductController::class, 'update']);

});

Route::middleware('auth:sanctum')->prefix('/v1')->group(function (){
    Route::middleware('merchant')->group(function () {
        //Category
        Route::post('category', [CategoryController::class, 'store']);
        Route::get('category', [CategoryController::class, 'index']);
        Route::get('all-category', [CategoryController::class, 'getAllCategory']);
        Route::get('category/{id}', [CategoryController::class, 'show']);
        Route::patch('category/{id}', [CategoryController::class, 'update']);
        Route::delete('category/{id}', [CategoryController::class, 'delete']);

        //Item
        Route::post('item', [ItemController::class, 'store']);
        Route::get('item', [ItemController::class, 'index']);
        Route::get('item/{id}', [ItemController::class, 'show']);
        Route::delete('item/{id}', [ItemController::class, 'delete']);
        Route::post('item/{id}', [ItemController::class, 'update']);
        Route::post('item/publish-request/{item}', [ItemController::class, 'itemPublishRequest']);
        Route::put('item/quantity/{id}', [ItemController::class, 'updateItemQuantity']);
        Route::put('item/low-stock/{id}', [ItemController::class, 'updateItemLowStockQuantity']);
        //Connect
        Route::post('connect', [ConnectController::class, 'store']);
        Route::get('connect', [ConnectController::class, 'index']);
        Route::get('connect/count', [ConnectController::class, 'connectCount']);
        Route::get('connect/{id}', [ConnectController::class, 'show']);
        Route::delete('connect/{id}', [ConnectController::class, 'delete']);
        Route::patch('connect/add-favorite/{id}',[ConnectController::class,'addConnectToFavorite']);
        Route::patch('connect/remove-favorite/{id}',[ConnectController::class,'removeConnectFromFavorite']);

        //Merchant Invoice
        Route::post('invoice', [InvoiceController::class, 'store']);
        Route::get('invoice', [InvoiceController::class, 'index']);
        Route::get('invoice/{id}', [InvoiceController::class, 'show']);
        Route::delete('invoice/{id}', [InvoiceController::class, 'delete']);
        Route::post('invoice-status-change/{status}', [InvoiceController::class, 'status_change']);
        Route::patch('invoice-mark-starred/{id}',[InvoiceController::class,'markInvoiceToStarred']);
        Route::patch('invoice-unmark-starred/{id}',[InvoiceController::class,'unmarkInvoiceFromStarred']);

        //transaction reports
        Route::get('item/sales/report', [MerchantReportController::class, 'salesReportItemWise']);
        Route::get('category/sales/report/{id}', [MerchantReportController::class, 'salesReportCategoryWise']);

        //store information settings
        Route::get('store-info', [StoreSettingsController::class, 'storeInfo']);
        Route::get('store-order-time', [StoreSettingsController::class, 'storeOrderTime']);
        Route::post('store-info/address-setting', [StoreSettingsController::class, 'storeAddressSettings']);
        Route::post('store-info/allow-service-setting', [StoreSettingsController::class, 'allowServices']);
        Route::post('store-info/order_prepare_time-setting', [StoreSettingsController::class, 'orderPrepareTime']);
        Route::post('store-info/allow_schedule_pickup-setting', [StoreSettingsController::class, 'allowSchedulePickup']);
        Route::post('store-info/instruction-setting', [StoreSettingsController::class, 'instructionSettings']);
        Route::post('store-info/pickup-dine-in-time-setting', [StoreSettingsController::class, 'pickupDineInTimeSet']);
        Route::post('store-info/enable-or-disable-store', [StoreSettingsController::class, 'enableOrDisableStore']);
        //Discount
        Route::get('discount',[DiscountController::class,'index']);
        Route::post('discount',[DiscountController::class,'store']);
        Route::put('discount/{id}',[DiscountController::class,'update']);
        Route::delete('discount/{id}',[DiscountController::class,'delete']);

        //Market Place Item's
        Route::get('items/marketplace',[MarketPlaceController::class,'getItems']);

        //Order Management Merchant
        Route::prefix('manage/orders')->group(function(){
            Route::get('/',[OrderManagementController::class,'index']);
            Route::get('/{id}',[OrderManagementController::class,'details']);
            Route::post('/status-update',[OrderManagementController::class,'statusUpdate']);
            Route::post('/verify-otp',[OrderManagementController::class,'verifyOtpForDelivery']);
        });

        //published item summary
        Route::get('published-item-summary', [MerchantSalesReportController::class, 'publishedItemSummary']);
        Route::get('order-summary', [DashboardController::class, 'orderSummary']);
    });

    //Common Invoice
    Route::get('invoice-statuses', [InvoiceController::class, 'get_all_status']);
    Route::get('bill-amount-receivable', [DashboardController::class, 'receivable']);
    Route::get('invoice-amount-payable', [DashboardController::class, 'payable']);
    Route::get('my-items', [DashboardController::class, 'my_items']);
    Route::get('total-sales', [DashboardController::class, 'total_sales']);

    //Receivable Invoice
    Route::get('receivable-invoice',[ConsumerInvoiceController::class,'index']);
    Route::get('receivable-invoice/{id}',[ConsumerInvoiceController::class,'show']);
    Route::post('receivable-invoice-status-change/{id}', [ConsumerInvoiceController::class, 'status_change']);

    //Privacy Settings for all user
    Route::post('privacy-settings', [PrivacySettingController::class,'store']);
    Route::get('privacy-settings', [PrivacySettingController::class,'index']);

    //Market Place Item's
    Route::get('/shop/item/{shopId}',[MarketPlaceController::class,'shopItems']);
    Route::get('/shop/item/search/{shopId}',[MarketPlaceController::class,'shopItemSearch']);
    Route::get('/shop/item/store-info/{shopId}',[MarketPlaceController::class,'storeInfo']);
    Route::get('/shop/item/{shopId}/{id}', [MarketPlaceController::class, 'show']);
    Route::get('/merchant/list',[MarketPlaceController::class,'merchantList']);
    Route::get('/business/category/list',[MarketPlaceController::class,'bussinesCategoryList']);
    Route::get('/shop/category/list/{id}',[MarketPlaceController::class,'shopCategoryList']);


    //Order Management personal
    Route::prefix('orders')->group(function(){
        Route::get('/',[OrderController::class,'index']);
        Route::get('/{id}',[OrderController::class,'details']);
        Route::post('/summary',[OrderController::class,'summary']);
        Route::post('/execution',[OrderController::class,'store']);
        Route::post('/cancel/{id}',[OrderController::class,'cancelOrder']);
    });

});
