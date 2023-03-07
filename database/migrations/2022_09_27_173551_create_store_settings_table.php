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
        Schema::create('store_settings', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id');
            $table->json('address')->nullable();
            $table->boolean('allow_pickup')->default(0);
            $table->boolean('allow_dine_in')->default(0);
            $table->json('pickup_and_dine_in_times')->nullable();
            $table->string('order_prepare_time')->nullable();
            $table->boolean('allow_schedule_pickup')->default(0);
            $table->string('instructions')->nullable();
            $table->dateTime('deleted_at')->nullable();
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
        Schema::dropIfExists('store_settings');
    }
};
