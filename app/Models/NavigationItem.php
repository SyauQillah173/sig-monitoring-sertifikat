<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'group_label',
    'label',
    'route_name',
    'icon',
    'sort_order',
    'allowed_roles',
    'is_active',
])]
class NavigationItem extends Model
{
    protected function casts(): array
    {
        return [
            'allowed_roles' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('group_label')->orderBy('label');
    }
}
