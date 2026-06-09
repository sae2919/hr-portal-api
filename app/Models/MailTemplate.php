<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MailTemplate extends Model
{
    use HasFactory;

    protected $table = 'mail_templates';

    protected $fillable = [
        'template_name',
        'type',
        'subject',
        'body',
        'style',
        'active_status',
    ];

    protected $casts = [
        'active_status' => 'integer',
    ];
}
