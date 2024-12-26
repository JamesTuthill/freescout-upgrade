<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateChecklistItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('checklist_items', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('conversation_id')->index();
            $table->unsignedTinyInteger('status')->default(1);
            //$table->string('name');
            $table->text('text')->nullable();
            $table->unsignedInteger('linked_conversation_id')->index();
            $table->unsignedInteger('linked_conversation_number');
            //$table->integer('sort_order')->default(1);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('checklist_items');
    }
}
