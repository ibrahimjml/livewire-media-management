<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MediaSetting extends Model
{
      protected $table = 'media_settings';

    protected $fillable = [
        'key',
        'value',
        'user_id',
    ];

    protected $casts = [
        'value' => 'json',
    ];
}
