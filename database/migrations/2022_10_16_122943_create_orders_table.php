<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id');
            $table->bigInteger('merchant_id');
            $table->string('invoice_id', 10)->unique();
            $table->string('transaction_id')->nullable();
            $table->unsignedTinyInteger('order_type')->comment("1=pickup;2=dine-in");
            $table->date('pickup_date')->nullable();
            $table->string('pickup_time')->nullable();
            $table->boolean('has_discount')->default(0)->comment("0=no;1=code;2=voucher");
            $table->unsignedDouble('total_item_price', 10, 2);
            $table->unsignedDouble('discount_amount',10,2)->default(0);
            $table->unsignedDouble('voucher_price',10,2)->default(0);
            $table->unsignedDouble('total_vat',10,2)->default(0);
            $table->unsignedDouble('service_fee',10,2)->default(0);
            $table->unsignedDouble('total_price', 10, 2);
            $table->json('discount_details')->nullable();
            $table->json('voucher_details')->nullable();
            $table->unsignedDouble('paid_amount', 10, 2)->default(0);
            $table->unsignedDouble('refund_amount', 10, 2)->default(0);
            $table->boolean('payment_status')->default(0);
            $table->dateTime('payment_date')->nullable();
            $table->string('user_note', 512)->nullable();
            $table->boolean('status')->default(0);
            $table->bigInteger('otp')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('orders');
    }
};
