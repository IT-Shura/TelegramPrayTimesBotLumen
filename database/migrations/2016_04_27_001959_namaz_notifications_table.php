<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class NamazNotificationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('namaz_notifications', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('users_id');
            $table->foreign('users_id')->references('id')->on('users');
            $table->bigInteger('date')->index();
            $table->smallInteger('namaz_type');
            $table->smallInteger('minutes');
            $table->timestamps();
        });
        
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('notifications')->default(false);
        });
        
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('namaz_notifications');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('notifications')->default(false);
        });
    }
}
