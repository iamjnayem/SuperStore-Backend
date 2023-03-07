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
        Schema::table('item_wise_total_reports', function (Blueprint $table) {
            if (!Schema::hasColumn('item_wise_total_reports', 'total_sale_amount_without_discount')) {
                $table->decimal('total_sale_amount_without_discount')->after('total_sale_amount');
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
        Schema::table('item_wise_total_reports', function (Blueprint $table) {
            $table->dropColumn('total_sale_amount_without_discount');
        });
    }
};
