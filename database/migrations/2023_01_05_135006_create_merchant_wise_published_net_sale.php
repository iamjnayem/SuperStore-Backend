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
        Schema::create('merchant_wise_published_net_sale', function (Blueprint $table) {
            $table->id();
            $table->unsignedBiginteger('merchant_id');
            $table->decimal('net_sale_amount', 12,2)->default(0.0);
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('merchant_wise_published_net_sale');
    }
};

