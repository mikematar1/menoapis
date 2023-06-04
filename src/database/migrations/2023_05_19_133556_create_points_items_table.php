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
        Schema::create('points_items', function (Blueprint $table) {
            $table->id();
            $table->integer("business_id");
            $table->string("item_name");
            $table->string("item_description");
            $table->string("item_imageurl",700);
            $table->integer("item_points");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('points_items');
    }
};
