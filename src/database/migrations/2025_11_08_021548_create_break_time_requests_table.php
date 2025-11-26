<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('break_time_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_request_id')->constrained()->onDelete('cascade');
            $table->foreignId('break_time_id')->nullable()->constrained()->onDelete('cascade');
            $table->datetime('before_start')->nullable();
            $table->datetime('before_end')->nullable();
            $table->datetime('after_start')->nullable();
            $table->datetime('after_end')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('break_time_requests');
    }
};
