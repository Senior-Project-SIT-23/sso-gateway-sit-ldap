<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersAuthCodeTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users_auth_code', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('user_id', 30)->cascade;
            $table->string('auth_code', 10);
            $table->timestamps();

            $table->foreign('user_id')->references('user_id')->on('users_auth')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users_auth_code');
    }
}
