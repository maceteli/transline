<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePaystackDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
   public function up()
    {
        Schema::create('paystack_details', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('paystack_id')->nullable();
            $table->integer('amount')->nullable();
            $table->string('currency')->nullable();
            $table->string('transaction_date')->nullable();
            $table->string('status')->nullable();
            $table->string('reference')->nullable();
            $table->string('domain')->nullable();
            $table->string('gateway_response')->nullable();
            $table->string('message')->nullable();
            $table->string('channel')->nullable();
            $table->integer('user_id')->nullable();
            $table->integer('request_id')->nullable();
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
        Schema::dropIfExists('paystack_details');
    }
}
