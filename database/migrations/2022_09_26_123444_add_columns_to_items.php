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
        Schema::table('items', function (Blueprint $table) {
            if (!Schema::hasColumn('items', 'stock_quantity')) {
                $table->integer('stock_quantity')->nullable()->after('description');
            }
            if (!Schema::hasColumn('items', 'low_stock_alert')) {
                $table->integer('low_stock_alert')->nullable()->after('stock_quantity');
            }
            if (!Schema::hasColumn('items', 'addons')) {
                $table->json('addons')->nullable()->after('low_stock_alert');
            }
            if (!Schema::hasColumn('items', 'is_publish')) {
                $table->integer('is_publish')->default(0)->after('addons');
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
        Schema::table('items', function (Blueprint $table) {
            $table->dropColumn('stock_quantity');
            $table->dropColumn('low_stock_alert');
            $table->dropColumn('addons');
        });
    }
};
