<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationTemplate extends Model
{
    protected $fillable = [
      'template_key',
		'channel',
		'language_code',
		'subject',
		'body_html',
		'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}