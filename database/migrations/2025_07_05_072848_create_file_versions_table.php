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
        Schema::create('file_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('file_id')->constrained()->onDelete('cascade');
            $table->integer('version_number');
            $table->string('file_path');

            // Add the uploader info
            $table->foreignId('uploaded_by')->constrained('users')->onDelete('cascade');

            $table->timestamps();
        });

        // Add foreign key to files.latest_version_id (after both tables are created)
        Schema::table('files', function (Blueprint $table) {
            $table->foreign('latest_version_id')->references('id')->on('file_versions')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop foreign key first
        Schema::table('files', function (Blueprint $table) {
            $table->dropForeign(['latest_version_id']);
        });

        Schema::dropIfExists('file_versions');
    }
};
