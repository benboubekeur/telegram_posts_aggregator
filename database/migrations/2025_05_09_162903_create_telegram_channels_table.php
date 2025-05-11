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
        Schema::create('telegram_channels', function (Blueprint $table) {
            $table->id();
            $table->string('url')->nullable()->unique();
            $table->string('title')->nullable();
            $table->text('about')->nullable();
            $table->string('channel_identifier')->unique(); // could be the channel name or unique ID extracted from the URL
            $table->string('channel_id')->unique(); // could be the channel name or unique ID extracted from the URL
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('telegram_channels');
    }
};
