<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('chat_sessions', function (Blueprint $table) {
            $table->id();
            // Hangi müşteriye ait olduğunu belirtir
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('session_id')->unique(); // Ziyaretçinin tarayıcı session ID'si
            $table->string('visitor_name')->nullable();
            $table->string('visitor_email')->nullable();
            $table->string('visitor_ip')->nullable();
            $table->enum('status', ['active', 'closed'])->default('active');
            $table->timestamp('last_activity')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_sessions');
    }
};
