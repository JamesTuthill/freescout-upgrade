<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateWallboardsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('wallboards', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 75);
            $table->text('widgets')->nullable(); // JSON
            // Default filters on a per user basis.
            //$table->text('filters'); // JSON
            $table->integer('visibility');
            $table->unsignedInteger('created_by_user_id');

            //$table->index(['created_by_user_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('wallboards');
    }
}
