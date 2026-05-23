<?php

namespace App\Models;

use App\Models\Concerns\AuditsModelChanges;
use Illuminate\Database\Eloquent\Model;

class NotificationSetting extends Model
{
    use AuditsModelChanges;

    protected $fillable = [
        'key',
        'value',
        'description',
    ];
}
