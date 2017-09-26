<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateIosDeviceRegistrationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ios_device_registrations', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
            $table->string('uuid');
            $table->string('device_id');
            $table->string('pass_type_id');
            $table->string('serial_number');
            $table->string('device_type');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ios_device_registrations');
    }
}
