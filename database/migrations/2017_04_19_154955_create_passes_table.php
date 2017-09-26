<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePassesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('passes', function (Blueprint $table) {
            $table->increments('id');
            $table->string('card_id');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('created_on');
            $table->string('email');
            $table->string('phone');
            $table->string('venue_name');
            $table->string('loyalty_program');
            $table->string('total_points');
            $table->string('serial_number');
            $table->string('authentication_token');
            $table->string('pass_type_id');
            $table->string('thumbnail');
            $table->string('contact_id');
            $table->string('date_of_birth');
            $table->string('owningteam');
            $table->text('voucher_data');
            $table->tinyinteger('pass_type');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('passes');
    }
}
