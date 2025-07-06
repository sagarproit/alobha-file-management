<?php


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('repository_user_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('repository_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('role', ['admin', 'write', 'read']);
            $table->timestamps();
            $table->unique(['repository_id', 'user_id']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('repository_user_roles');
    }
};
