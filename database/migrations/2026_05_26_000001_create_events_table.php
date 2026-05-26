<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('title')->default('Quinceañera');
            $table->text('description')->nullable();
            $table->dateTimeTz('starts_at');
            $table->integer('capacity')->default(100);
            $table->integer('max_guests_per_registration')->default(4);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('events');
    }
};
