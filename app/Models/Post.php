<?php

namespace App\Models;

use App\Models\Scopes\PostScope;
use App\Models\Scopes\UserScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Post extends Model
{
    use HasFactory;

    protected $fillable = [
        "user_id",
        "api_key_id",
        "post_id",
        "account_id",
        "social_type",
        "type",
        "source",
        "title",
        "description",
        "comment",
        "domain_id",
        "url",
        "image",
        "video",
        "publish_date",
        "status",
        "published_at",
        "scheduled",
        "response"
    ];

    protected $appends = ["date", "time", "modal_time", "message"];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function apiKey()
    {
        return $this->belongsTo(ApiKey::class, 'api_key_id', 'id');
    }

    public function board()
    {
        return $this->belongsTo(Board::class, 'account_id', 'id');
    }

    public function page()
    {
        return $this->belongsTo(Page::class, 'account_id', 'id');
    }

    public function domain()
    {
        return $this->belongsTo(Domain::class, 'domain_id', 'id');
    }
    public function photo()
    {
        return $this->hasOne(Photo::class, 'post_id', 'id');
    }

    public function scopeSchedule($query)
    {
        $query->where("scheduled", 1);
    }

    public function scopeNotSchedule($query)
    {
        $query->where("scheduled", 0);
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
        if (isset($search["account_id"])) {
            $query->where("account_id", $search["account_id"]);
        }
        if (isset($search["social_type"])) {
            $query->where("social_type", $search["social_type"]);
        }
        if (isset($search["type"])) {
            $query->where("type", $search["type"]);
        }
        if (isset($search["source"])) {
            $query->where("source", $search["source"]);
        }
        if (isset($search["domain_id"])) {
            $query->where("domain_id", $search["domain_id"]);
        }
        if (isset($search["url"])) {
            $query->where("url", "like", "%" . $search["url"] . "%");
        }
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
        if (is_array($id) && count($id) > 0) {
            $query->whereIn("domain_id", $id);
        } else {
            $query->where("domain_id", $id);
        }
    }

    public function scopeAccounts($query, $id)
    {
        if (is_array($id) && count($id) > 0) {
            $query->whereIn("account_id", $id);
        } else {
            $query->where("account_id", $id);
        }
    }

    public function scopePublished($query)
    {
        $query->where("status", "1");
    }

    public function scopeNotPublished($query)
    {
        $query->whereIn("status", ["0"]);
    }

    public function scopePinterest($query)
    {
        $query->where("social_type", "like", "%pinterest%");
    }

    public function scopeFacebook($query)
    {
        $query->where("social_type", "like", "%facebook%");
    }

    public function scopePast($query, $date_time)
    {
        $query->where("publish_date", "<=", $date_time);
    }

    public function scopeNext($query, $date_time)
    {
        $query->where("publish_date", ">=", $date_time);
    }

    public function scopeIsRss($query)
    {
        $query->whereIn("source", ["rss"]);
    }

    public function scopeIsScheduled($query)
    {
        $query->whereIn("source", ["schedule"]);
    }

    public function getAccount($social_type, $id)
    {
        if ($social_type == 'pinterest') {
            $account = $this->board()->where("board_id", $id)->first();
        }
        if ($social_type == 'facebook') {
            $account = $this->page()->where("page_id", $id)->first();
        }
        return $account;
    }

    public function getAccountUrl($social_type, $account_id)
    {
        $account = self::getAccount($social_type, $account_id);
        if ($social_type == 'pinterest') {
            $mainAccount = $account->getPinterest($account->pin_id);
            $accountUrl = "https://www.pinterest.com/" . $mainAccount->username . '/' . $account->name;
        }
        if ($social_type == 'facebook') {
            $accountUrl = "https://www.facebook.com/" . $account->page_id;
        }
        return $accountUrl;
    }

    public function nextTime($search, $time)
    {
        $lastPost = $this->exist($search)->orderByDesc('publish_date')->first();
        if ($lastPost) {
            $lastPublisDate = $lastPost->publish_date;
            $nextDate = date("Y-m-d", strtotime($lastPublisDate . " +1 days"));
        } else {
            $hour = strtotime(date("H:i"));
            $time = strtotime($time);
            if ($time > $hour) {
                $nextDate = date("Y-m-d");
            } else {
                $now = date("Y-m-d");
                $nextDate = date("Y-m-d", strtotime($now . ' +1 days'));
            }
        }
        return $nextDate;
    }
    public function nextScheduleTime($search, $timeslots)
    {
        $current_hour = strtotime(date("H:i"));
        $lastPost = $this->exist($search)->orderByDesc('publish_date')->exists();
        if (!$lastPost) { //if no post exists
            foreach ($timeslots as $timeslot) {
                $posting_hour = strtotime($timeslot->timeslot);
                //if current hour is greater or equal to posting hour, skip it
                if ($current_hour > $posting_hour)
                    continue;
                // if posting hour is greater than current hour, create a post for current day
                if ($posting_hour >= $current_hour) {
                    $selectedDate = date("Y-m-d");
                    break;
                }
            }
            if (!isset($selectedDate)) {
                $selectedDate = date("Y-m-d", strtotime("+1 days"));
                $timeslot = $timeslots->first();
                $posting_hour = strtotime($timeslot->timeslot);
            }
            $timeslotDateTime = $selectedDate . " " . date("H:i", $posting_hour);
            return $timeslotDateTime;
        }
        if ($lastPost) { //if post exists
            $checkPost = true;
            $offset = 0;
            foreach ($timeslots as $timeslot) {
                $checkPost = $this->exist($search)->where("publish_date", "like", "%$timeslot->timeslot%")->exists();
                if ($checkPost) { //continue if post exists
                    $offset++;
                    continue;
                }
                if (!$checkPost) { //if no post exists
                    $posting_hour = strtotime($timeslot->timeslot);
                    //if current hour is greater or equal to posting hour, skip it
                    if ($current_hour > $posting_hour)
                        continue;
                    // if posting hour is greater than current hour, create a post for current day
                    if ($posting_hour >= $current_hour) {
                        $selectedDate = date("Y-m-d");
                    }
                    break;
                }
            }
            if (!$checkPost) {
                if (!isset($selectedDate)) {
                    $selectedDate = date("Y-m-d", strtotime("+1 days"));
                    $timeslot = $timeslots->skip($offset)->first();
                    $posting_hour = strtotime($timeslot->timeslot);
                }
                $timeslotDateTime = $selectedDate . " " . date("H:i", $posting_hour);
                return $timeslotDateTime;
            }
            if ($checkPost) {
                $lastPost = $this->exist($search)->latest()->first();
                $lastPostDate = date("Y-m-d", strtotime($lastPost->publish_date));
                $nextPostDate = date("Y-m-d", strtotime($lastPostDate . ' +1 day'));
                $lastPostTime = date("H:i", strtotime($lastPost->publish_date));
                $offset = 0;
                foreach ($timeslots as $timeslot) {
                    $posting_hour = $timeslot->timeslot;
                    if ($lastPostTime == $posting_hour) {
                        $nextPostingHour = isset($timeslots[$offset + 1]) ? $timeslots[$offset + 1] : $timeslots[0];
                    }
                    $offset++;
                }
                if (!isset($nextPostingHour)) {
                    $nextPostingHour = $timeslots[0];
                }
                $posting_hour = $nextPostingHour->timeslot;
                $selectedDate = $nextPostDate;
                $timeslotDateTime = $selectedDate . " " . $posting_hour;
                return $timeslotDateTime;
            }
        }
    }

    public function publishDate($date, $time)
    {
        $date_time = $date . ' ' . $time;
        return $date_time;
    }

    public function scheduledTill($search = null, $social_type, $account, $domain, $status, $user_id)
    {
        $post = $this->orderBy('publish_date', 'DESC');
        if ($account) {
            if ($social_type == 'pinterest') {
                $account = Board::findOrFail($account);
            }
            if ($social_type == 'facebook') {
                $account = Page::findOrFail($account);
            }
            $post = $post->where("account_id", $account->id);
        }
        if (count($domain) > 0) {
            $post = $post->whereIn("domain_id", $domain);
        }
        if (in_array($status, ['-1', '0', '1'])) {
            $post = $post->where("status", $status);
        }
        $post = $post->first();
        return $post ? date("jS M, Y h:i A", strtotime($post->publish_date)) : 'NA';
    }

    protected function title(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                $title = !empty($value) ? htmlspecialchars_decode($value) : $value;
                return $title;
            }
        );
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

    public function getVideoKeyAttribute()
    {
        $video_key = $this->video;
        return !empty($video_key) ? fetchFromS3($video_key) : '';
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

    protected function message(): Attribute
    {
        return Attribute::make(
            get: function () {
                $message = '';
                if ($this->status == -1) {
                    $response = $this->response;
                    if (!empty($response)) {
                        $response = json_decode($response);
                        if ($this->social_type == 'pinterest') {
                            if (!empty($response)) {
                                $message_object = $response->message;
                                $message = getError($message_object);
                            }
                        } else {
                            $message = $response;
                        }
                    }
                }
                return $message;
            }
        );
    }

    public function getAccountNameAttribute()
    {
        $social_type = $this->social_type;
        $account_name = '';
        if ($social_type == "facebook") {
            $account_name = $this->page?->name;
        }
        if ($social_type == "pinterest") {
            $account_name = $this->board?->name;
        }
        return $account_name;
    }

    public function getAccountProfileAttribute()
    {
        $social_type = $this->social_type;
        $profile_image = '';
        if ($social_type == "facebook") {
            $profile_image = $this->page?->facebook ? $this->page->facebook->profile_image : null;
        }
        if ($social_type == "pinterest") {
            $profile_image = $this->board?->pinterest ? $this->board->pinterest->profile_image : null;
        }
        return $profile_image;
    }

    public function getPostDetailsAttribute()
    {
        if ($this->social_type == "facebook") {
            $view = view("user.schedule.dataTable.facebook_post_details")->with("post", $this);
        }
        if ($this->social_type == "pinterest") {
            $view = view("user.schedule.dataTable.pinterest_post_details")->with("post", $this);
        }
        return $view->render();
    }

    public function getAccountDetailAttribute()
    {
        $view = view("user.schedule.dataTable.account_detail")->with("post", $this);
        return $view->render();
    }

    public function getPublishDateTimeAttribute()
    {
        $publish_datetime = $this->publish_date;
        return date("Y-m-d h:i A", strtotime($publish_datetime));
    }

    public function getStatusViewAttribute()
    {
        $view = view("user.schedule.dataTable.status_view")->with("post", $this);
        return $view->render();
    }

    public function getActionAttribute()
    {
        $view = view("user.schedule.dataTable.action")->with("post", $this);
        return $view->render();
    }

    public function getIsPinterestAttribute()
    {
        return $this->social_type == "pinterest" ? true : false;
    }

    public function getDomainNameAttribute()
    {
        $domain = $this->domain;
        return $domain ? $domain->name : "";
    }

    public function getFixAttribute()
    {
        $title = $this->title;
        $image = $this->image;
        return empty($title) || empty($image) ? true : false;
    }

    public function getApiKeyNameAttribute()
    {
        return $this->apiKey?->name ?? '-';
    }

    protected static function booted()
    {
        static::addGlobalScope(new UserScope);
    }
}
