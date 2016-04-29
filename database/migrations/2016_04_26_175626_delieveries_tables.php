<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class DelieveriesTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('deliveries_types', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 255);
            $table->string('description', 255);
            $table->boolean('enabled_by_default')->default(false);
            $table->boolean('superadmin')->default(false);
        });
        
        Schema::create('deliveries_statuses', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 255);
        });
        
        Schema::create('deliveries', function (Blueprint $table) {
            $table->increments('id');
            $table->text('text')->nullable();
            $table->string('file', 1000)->nullable();
            $table->string('file_type', 255)->nullable();
            $table->integer('type_id');   $table->foreign('type_id')->references('id')->on('deliveries_types');
            $table->integer('status_id'); $table->foreign('status_id')->references('id')->on('deliveries_statuses');
            $table->integer('author_id'); $table->foreign('author_id')->references('id')->on('users');
            $table->timestamps();
        });
        
        Schema::create('users_has_delivery_types', function (Blueprint $table) {
            $table->integer('users_id');
            $table->foreign('users_id')->references('id')->on('users');
            $table->integer('delivery_types_id');
            $table->foreign('delivery_types_id')->references('id')->on('deliveries_types');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('users_has_delivery_types');
        Schema::drop('deliveries');
        Schema::drop('deliveries_statuses');
        Schema::drop('deliveries_types');
    }
}
