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
        Schema::table('invoice_transaction_category_wises', function (Blueprint $table) {
            if (!Schema::hasColumn('invoice_transaction_category_wises', 'total_price_without_discount')) {
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
        Schema::table('invoice_transaction_category_wises', function (Blueprint $table) {
            $table->dropColumn('total_price_without_discount');
        });
    }
};
