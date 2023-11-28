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
        Schema::create('games', function (Blueprint $table) {
			$table->increments('id');
			$table->smallInteger('number');
			$table->string('beat_user_name', 200);
			$table->tinyInteger('beat_user_age');
			$table->smallInteger('beat_user_time');
			$table->tinyInteger('beat_user_tries');
			$table->integer('start_playing');
			$table->text('user_playing')->nullable()->default('NULL');
			//$table->integer('play_start');
			$table->datetime('updated_at')->nullable()->default('NULL');
			$table->tinyInteger('status');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('games');
    }
};
