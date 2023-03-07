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
        Schema::create('discounts', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('merchant_id');
            $table->json('categories')->nullable();
            $table->json('items')->nullable();
            $table->string('title');
            $table->boolean('is_percentage');
            $table->integer('amount');
            $table->json('discount_schedule')->nullable();
            $table->unsignedTinyInteger('duration_type')->comment("1=4ever;2=daterange");
            $table->dateTime('start_date')->nullable();
            $table->dateTime('end_date')->nullable();
            $table->unsignedInteger('max_use')->default(0)->nullable();
            $table->unsignedInteger('use_count')->default(0);
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
        Schema::dropIfExists('discounts');
    }
};
