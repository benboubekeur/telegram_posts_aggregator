<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TelegramMessage extends Model
{
    /** @use HasFactory<\Database\Factories\TelegramMessageFactory> */
    use HasFactory;

    public function telegramChannel() : BelongsTo
    {
        return $this->belongsTo(TelegramChannel::class);
    }
}
