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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_user_id');
            $table->foreignId('user_id');
            $table->foreignId('connect_id');
            $table->string('invoice_id');
            $table->string('custom_invoice_id')->nullable();
            $table->string('title')->nullable();
            $table->decimal('total_bill_amount','10','2');
            $table->decimal('final_bill_amount','10','2')->comment('sub_total');
            $table->decimal('discount_amount','10','2');
            $table->decimal('delivery_charge_amount','10','2');
            $table->decimal('vat_tax_amount','10','2');
            $table->string('notification_method');
            $table->string('expire_after');
            $table->date('expire_at_date')->nullable();
            $table->time('expire_at_time')->nullable();
            $table->date('schedule_at_date')->nullable();
            $table->time('schedule_at_time')->nullable();
            $table->string('reminder')->nullable();
            $table->date('due_date')->nullable();
            $table->date('reminder_at_date')->nullable();
            $table->time('reminder_at_time')->nullable();
            $table->string('notes')->nullable();
            $table->tinyInteger('status')->default(1);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('invoices');
    }
};
