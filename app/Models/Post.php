<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Post extends Model
{
    use HasFactory;

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
        "response"
    ];

    protected $appends = ["date", "time", "modal_time"];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function board()
    {
        return $this->belongsTo(Board::class, 'account_id', 'board_id');
    }

    public function page()
    {
        return $this->belongsTo(Page::class, 'account_id', 'page_id');
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

    public function scopeUserSearch($query, $id)
    {
        $query->where("user_id", $id);
    }

    public function scopeAccountExist($query)
    {
        $query->has("page")->orHas("board");
    }

    public function scopePageExist($query)
    {
        $query->has("page");
    }

    public function scopeBoardExist($query)
    {
        $query->has("board");
    }

    public function scopeDomainSearch($query, $id)
    {
        $query->whereIn("domain_id", $id);
    }

    public function scopeAccounts($query, $id)
    {
        $query->whereIn("account_id", $id);
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

    public function getAccount($type, $id)
    {
        if ($type == 'pinterest') {
            $account = $this->board()->where("board_id", $id)->first();
        }
        if ($type == 'facebook') {
            $account = $this->page()->where("page_id", $id)->first();
        }
        return $account;
    }

    public function getAccountUrl($type, $account_id)
    {
        $account = self::getAccount($type, $account_id);
        if ($type == 'pinterest') {
            $mainAccount = $account->getPinterest($account->pin_id);
            $accountUrl = "https://www.pinterest.com/" . $mainAccount->username . '/' . $account->name;
        }
        if ($type == 'facebook') {
            $accountUrl = "https://www.facebook.com/" . $account->page_id;
        }
        return $accountUrl;
    }

    public function nextTime($search)
    {
        $lastPost = $this->exist($search)->latest()->first();
        if ($lastPost) {
            $lastPublisDate = $lastPost->publish_date;
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

    public function scheduledTill($search, $type, $account, $domain, $status)
    {
        $post = $this->orderBy('publish_date', 'DESC');
        if (!empty($search)) {
            $post = $post->search($search);
        }
        if ($account) {
            if ($type == 'pinterest') {
                $account = Board::find($account);
                $account = $account->board_id;
            }
            if ($type == 'facebook') {
                $account = Page::find($account);
                $account = $account->page_id;
            }
            $post = $post->where("account_id", $account);
        }
        if (count($domain) > 0) {
            $post = $post->whereIn("domain_id", $domain);
        }
        if (in_array($status, ['-1', '0', '1'])) {
            $post = $post->where("status", $status);
        }
        $post = $post->first();
        return $post ? date("Y-m-d h:i A", strtotime($post->publish_date)) : '-';
    }

    protected function image(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                if (!empty($value) && str_contains($value, "http")) {
                    return $value;
                } else {
                    $image = !empty($value) ? url(getImage('', $value)) : no_image();
                    return $image;
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
                $time = date("h:i A", strtotime($this->publish_date));
                return $time;
            }
        );
    }

    protected function modalTime(): Attribute
    {
        return Attribute::make(
            get: function () {
                $time = date("H:i", strtotime($this->publish_date));
                return $time;
            }
        );
    }
}
