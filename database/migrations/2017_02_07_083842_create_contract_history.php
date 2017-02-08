<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateContractHistory extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('contract_history', function (Blueprint $table) {
            $table->increments('id');
            $table->bigInteger('contract_id')->unsigned()->index();
            // $table->decimal('last_trade_price', 3, 2)->nullable();
            // $table->decimal('best_buy_yes_cost', 3, 2)->nullable();
            // $table->decimal('best_buy_no_cost', 3, 2)->nullable();
            // $table->decimal('best_sell_yes_cost', 3, 2)->nullable();
            // $table->decimal('best_sell_no_cost', 3, 2)->nullable();
            // $table->decimal('last_close_price', 3, 2)->nullable();
            $table->decimal('last_trade_price', 3, 2);
            $table->decimal('best_buy_yes_cost', 3, 2);
            $table->decimal('best_buy_no_cost', 3, 2);
            $table->decimal('best_sell_yes_cost', 3, 2);
            $table->decimal('best_sell_no_cost', 3, 2);
            $table->decimal('last_close_price', 3, 2);
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
        Schema::dropIfExists('contract_history');
    }
}
