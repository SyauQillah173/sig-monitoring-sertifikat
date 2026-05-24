<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StoredFile extends Model
{
    protected $fillable = [
        'path',
        'original_name',
        'mime_type',
        'size',
        'contents',
    ];
}
