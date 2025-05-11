<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TelegramChannel extends Model
{
    /** @use HasFactory<\Database\Factories\TelegramChannelFactory> */
    use HasFactory;

    public function messages()  : \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(TelegramMessage::class);
    }
}
