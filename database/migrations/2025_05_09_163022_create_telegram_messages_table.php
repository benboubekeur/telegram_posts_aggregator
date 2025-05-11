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
        Schema::create('telegram_messages', function (Blueprint $table) {
            $table->id();

            $table->string('message_id');
            $table->string('peer_type'); // 'channel', 'chat', or 'user'
            $table->string('peer_id')->nullable();
            $table->string('from_id')->nullable();
            $table->text('message_content');
            $table->timestamp('sent_at');
            $table->foreignId('telegram_channel_id')->nullable()->constrained()->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('telegram_messages');
    }
};
