<?php
/**
 * Workflows processed for conversations.
 */
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCounterColumnToConversationWorkflowTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('conversation_workflow', function (Blueprint $table) {
            $table->integer('counter')->default(1);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('conversation_workflow', function (Blueprint $table) {
            $table->dropColumn('counter');
        });
    }
}
