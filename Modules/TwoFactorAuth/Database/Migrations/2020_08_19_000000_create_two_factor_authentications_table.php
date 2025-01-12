<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTwoFactorAuthenticationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('two_factor_authentications', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('user_id')->nullable()->index();
            $table->binary('shared_secret');
            $table->timestampTz('enabled_at')->nullable();
            $table->string('label');
            // $table->unsignedTinyInteger('digits')->default(6);
            // $table->unsignedTinyInteger('seconds')->default(30);
            // $table->unsignedTinyInteger('window')->default(0);
            // $table->string('algorithm', 16)->default('sha1');
            $table->text('recovery_codes')->nullable();
            $table->timestampTz('recovery_codes_generated_at')->nullable();
            $table->text('safe_devices')->nullable();
            $table->timestampsTz();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('two_factor_authentications');
    }
}
