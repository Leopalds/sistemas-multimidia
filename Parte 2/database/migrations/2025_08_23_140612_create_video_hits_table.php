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
        Schema::create('video_hits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('media_id')->constrained('media')->cascadeOnDelete();
            $table->foreignId('person_id')->constrained('people')->cascadeOnDelete();
            $table->unsignedInteger('frame_index');
            $table->decimal('timestamp_s', 10, 3);
            $table->unsignedInteger('left');
            $table->unsignedInteger('top');
            $table->unsignedInteger('right');
            $table->unsignedInteger('bottom');
            $table->float('distance');
            $table->timestamps();
        
            $table->index(['media_id', 'person_id']);
            $table->index('timestamp_s');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('video_hits');
    }
};
