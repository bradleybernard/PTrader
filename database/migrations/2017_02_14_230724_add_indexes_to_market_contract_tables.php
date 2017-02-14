<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIndexesToMarketContractTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('markets', function (Blueprint $table) {
            $table->index('market_id');
        });

        Schema::table('contracts', function (Blueprint $table) {
            $table->index('contract_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('markets', function (Blueprint $table) {
            $table->dropIndex(['market_id']);
        });

        Schema::table('contracts', function (Blueprint $table) {
            $table->dropIndex(['contract_id']);
        });
    }
}
