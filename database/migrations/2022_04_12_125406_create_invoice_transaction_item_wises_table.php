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
        Schema::create('invoice_transaction_item_wises', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_transaction_id');
            $table->bigInteger('item_id');
            $table->bigInteger('quantity');
            $table->string('unit');
            $table->decimal('unit_price');
            $table->decimal('total_price');
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
        Schema::dropIfExists('invoice_transaction_item_wises');
    }
};
