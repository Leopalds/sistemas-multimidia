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
        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->string("path");
            $table->enum("type", ["photo", "video"]);
            $table->json("meta")->nullable();
            $table->string("status")->default("queued"); // queued|processing|processed|failed
            $table->timestamps();

            $table->index("status");
            $table->index("type");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('media');
    }
};
