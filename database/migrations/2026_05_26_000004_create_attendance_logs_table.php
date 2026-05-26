<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('attendance_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('registration_id')->nullable()->constrained('registrations')->nullOnDelete();
            $table->foreignId('registration_person_id')->nullable()->constrained('registration_people')->nullOnDelete();
            $table->string('scanned_by')->nullable();
            $table->timestampTz('scanned_at')->useCurrent();
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('attendance_logs');
    }
};
