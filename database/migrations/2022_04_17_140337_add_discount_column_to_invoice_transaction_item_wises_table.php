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
        Schema::table('invoice_transaction_item_wises', function (Blueprint $table) {
            if (!Schema::hasColumn('invoice_transaction_item_wises', 'unit_price_without_discount')) {
                $table->decimal('unit_price_without_discount')->after('unit_price');
            }
            if (!Schema::hasColumn('invoice_transaction_item_wises', 'total_price_without_discount')) {
                $table->decimal('total_price_without_discount')->after('total_price');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('invoice_transaction_item_wises', function (Blueprint $table) {
            $table->dropColumn('unit_price_without_discount');
            $table->dropColumn('total_price_without_discount');
        });
    }
};
