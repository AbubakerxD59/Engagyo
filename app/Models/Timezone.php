<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Timezone extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'offset',
        'abbr',
    ];

    /**
     * Get a formatted display name for the timezone.
     *
     * @return string
     */
    public function getDisplayNameAttribute(): string
    {
        return "({$this->offset}) {$this->name}";
    }
}
