<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @method static \Illuminate\Database\Eloquent\Builder<static> query()
 */
class SearchQuery extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'term',
        'count',
        'last_searched_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'count' => 'integer',
            'last_searched_at' => 'datetime',
        ];
    }
}
