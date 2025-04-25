<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Post extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        "user_id",
        "post_id",
        "account_id",
        "type",
        "title",
        "description",
        "comment",
        "domain_id",
        "url",
        "image",
        "publish_date",
        "status",
    ];

    protected $appends = ["date", "time"];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function board()
    {
        return $this->belongsTo(Board::class, 'account_id', 'board_id');
    }

    public function domain()
    {
        return $this->belongsTo(Domain::class, 'domain_id', 'id');
    }

    public function scopeSearch($query, $search)
    {
        $query->where("title", "like", "%{$search}%")
            ->orWhere(function ($q) use ($search) {
                $q->whereHas('domain', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                });
            });
    }

    public function scopeExist($query, $search)
    {
        $query->where("user_id", $search["user_id"])
            ->where("account_id", $search["account_id"])
            ->where("type", $search["type"])
            ->where("domain_id", $search["domain_id"]);
        if (isset($search["url"])) {
            $query->where("url", "like", "%" . $search["url"] . "%");
        }
    }

    public function scopePublished($query)
    {
        $query->where("status", "1");
    }

    public function scopeNotPublished($query)
    {
        $query->whereIn("status", ["0", "-1"]);
    }

    public function scopePinterest($query)
    {
        $query->where("type", "like", "%pinterest%");
    }

    public function getDomain()
    {
        $domain = $this->domain()->first();
        return $domain ? $domain->name : '';
    }

    public function getAccount($type)
    {
        if ($type == 'pinterest') {
            $account = $this->board()->first();
        }
        return $account;
    }

    public function getAccountUrl($type)
    {
        $account = self::getAccount($type);
        if ($type == 'pinterest') {
            $mainAccount = $account->getPinterest();
            $accountUrl = "https://www.pinterest.com/" . $mainAccount->username . '/' . $account->name;
        }
        return $accountUrl;
    }

    public function nextTime($search)
    {
        $lastPost = $this->exist($search)->latest()->first();
        if ($lastPost) {
            $lastPublisDate = date("Y-m-d H:i:s", strtotime($lastPost->publish_date));
            $nextDate = date("Y-m-d", strtotime($lastPublisDate . " +1 days"));
        } else {
            $nextDate = date("Y-m-d");
        }
        return $nextDate;
    }

    public function publishDate($date, $time)
    {
        $date_time = $date . ' ' . $time;
        return $date_time;
    }

    protected function image(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                if (str_contains($value, "http")) {
                    return $value;
                } else {
                    return !empty($value) ? url(getImage('', $value)) : no_image();
                }
            }
        );
    }

    protected function date(): Attribute
    {
        return Attribute::make(
            get: function () {
                $date = date("Y-m-d", strtotime($this->publish_date));
                return $date;
            }
        );
    }

    protected function time(): Attribute
    {
        return Attribute::make(
            get: function () {
                $time = date("H:i", strtotime($this->publish_date));
                return $time;
            }
        );
    }
}
