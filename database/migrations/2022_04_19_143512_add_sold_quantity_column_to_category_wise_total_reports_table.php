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
        Schema::table('category_wise_total_reports', function (Blueprint $table) {
            if (!Schema::hasColumn('category_wise_total_reports', 'sold_quantity')) {
                $table->integer('sold_quantity')->default(0)->after('total_sale_amount_without_discount');
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
        Schema::table('category_wise_total_reports', function (Blueprint $table) {
            $table->dropColumn('sold_quantity');
        });
    }
};
