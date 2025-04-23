<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Post extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        "user_id",
        "post_id",
        "account_id",
        "type",
        "title",
        "decription",
        "comment",
        "domain_id",
        "url",
        "image",
        "publish_date",
        "status",
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function pinterest()
    {
        return $this->belongsTo(Pinterest::class, 'account_id', 'id')->where('type', 'like', '%pinterest%');
    }

    public function domain()
    {
        return $this->belongsTo(Domain::class, 'domain_id', 'id');
    }

    public function scopeSearch($query, $search)
    {
        $query->where("title", "%{$search}%")
            ->orWhere(function ($q) use ($search) {
                $q->whereHas('domain', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                });
            });
    }

    public function getDomain(){
        $domain = $this->domain()->first();
        return $domain ? $domain->name : '';
    }
}
