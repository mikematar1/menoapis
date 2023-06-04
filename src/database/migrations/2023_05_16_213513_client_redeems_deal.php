<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create("client_redeems_deal",function(Blueprint $table){
            $table->id();
            $table->integer("client_id");
            $table->integer("deal_id");
            $table->integer("status"); //1 for completed and 0 for pending
            $table->string("qrcodeurl",700);
            $table->integer("saved");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
