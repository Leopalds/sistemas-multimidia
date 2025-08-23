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
        Schema::create('faces', function (Blueprint $table) {
            $table->id();
            $table->foreignId('person_id')->constrained('people')->cascadeOnDelete();
            // 128 floats (4 bytes) = 512 bytes -> pode ser BLOB
            $table->binary('encoding');              // BLOB com float32[128]
            $table->string('source')->nullable();    // ex: "image:foto.jpg" ou "video:clip.mp4@12.3s"
            $table->timestamps();
        
            $table->index('person_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('faces');
    }
};
