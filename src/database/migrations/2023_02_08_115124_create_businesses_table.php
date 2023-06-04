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
        Schema::create('businesses', function (Blueprint $table) {
            $table->integer("user_id");
            $table->string("name");
            $table->string("location");
            $table->time("closing_hours");
            $table->time("opening_hours");
            $table->string("description");
            $table->string("logo_url",700);
            $table->integer("category_id");
            $table->integer("employee_id");
            $table->string("fb_link");
            $table->string("ig_link");
            $table->double("wallet");
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
        Schema::dropIfExists('businesses');
    }
};
