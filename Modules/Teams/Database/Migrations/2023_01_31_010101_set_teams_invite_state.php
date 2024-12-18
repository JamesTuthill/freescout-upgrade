<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class SetTeamsInviteState extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('users')->where('status', 3) // User::STATUS_DELETED
            ->where('email', 'like', 'fsteam%@example.org%')
            ->update(['invite_state' => 1]); // User::INVITE_STATE_ACTIVATED
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('users')->where('status', 3) // User::STATUS_DELETED
            ->where('email', 'like', 'fsteam%@example.org%')
            ->update(['type' => 3]);
    }
}
