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

    protected $appends = ["date", "time", "modal_time", "message", "response_message"];

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

    public function tiktok()
    {
        return $this->belongsTo(Tiktok::class, 'account_id', 'id');
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

    public function scopeTiktok($query)
    {
        $query->where("social_type", "like", "%tiktok%");
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
        if ($social_type == 'tiktok') {
            $account = $this->tiktok()->where("id", $id)->first();
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


    public function nextTime($search, array $times)
    {
        // Sort timeslots chronologically
        usort($times, function ($a, $b) {
            $timeA = strtotime($a);
            $timeB = strtotime($b);
            return $timeA - $timeB;
        });

        $currentDateTime = now();
        $currentDate = $currentDateTime->format('Y-m-d');
        $currentTime = $currentDateTime->format('H:i:s');

        // Get the last post to determine starting point
        $lastPost = $this->exist($search)->orderByDesc('publish_date')->first();

        if ($lastPost) {
            // If there's a last post, check each timeslot starting from the last post's date
            $lastPostDate = date('Y-m-d', strtotime($lastPost->publish_date));
            $lastPostTime = date('H:i:s', strtotime($lastPost->publish_date));

            // Find the index of the last post's timeslot
            $lastTimeslotIndex = -1;
            foreach ($times as $idx => $timeslot) {
                $timeslot24Hour = date('H:i:s', strtotime($timeslot));
                if ($lastPostTime == $timeslot24Hour) {
                    $lastTimeslotIndex = $idx;
                    break;
                }
            }

            // Start checking from the next timeslot
            $startDate = $lastPostDate;
            $timeslotIndex = ($lastTimeslotIndex >= 0) ? ($lastTimeslotIndex + 1) : 0;

            // Track used timeslots for each date
            $usedTimeslotsByDate = [];

            // Check posts to see which timeslots are already used
            $existingPosts = $this->exist($search)
                ->where('status', '!=', 1) // Not published
                ->orderBy('publish_date', 'ASC')
                ->get();

            foreach ($existingPosts as $post) {
                $postDate = date('Y-m-d', strtotime($post->publish_date));
                $postTime = date('H:i:s', strtotime($post->publish_date));
                if (!isset($usedTimeslotsByDate[$postDate])) {
                    $usedTimeslotsByDate[$postDate] = [];
                }
                $usedTimeslotsByDate[$postDate][] = $postTime;
            }

            // Find next available timeslot
            $attempts = 0;
            $maxAttempts = count($times) * 100; // Safety limit
            $selectedDate = null;
            $selectedTimeslot = null;

            while ($attempts < $maxAttempts && !$selectedDate) {
                // Initialize used timeslots array for current date if not exists
                if (!isset($usedTimeslotsByDate[$startDate])) {
                    $usedTimeslotsByDate[$startDate] = [];
                }

                // Get current timeslot
                $timeslot = $times[$timeslotIndex % count($times)];
                $timeslot24Hour = date('H:i:s', strtotime($timeslot));
                $timeslotKey = $timeslot24Hour;

                // Check if timeslot is already used for this date
                if (in_array($timeslotKey, $usedTimeslotsByDate[$startDate])) {
                    // Timeslot already used, try next timeslot
                    $timeslotIndex++;
                    if ($timeslotIndex >= count($times)) {
                        // All timeslots used for this day, move to next day
                        $startDate = date('Y-m-d', strtotime($startDate . ' +1 day'));
                        $timeslotIndex = 0;
                        if (!isset($usedTimeslotsByDate[$startDate])) {
                            $usedTimeslotsByDate[$startDate] = [];
                        }
                    }
                    $attempts++;
                    continue;
                }

                // Check if timeslot has passed for current day
                if ($startDate == $currentDate && $timeslot24Hour <= $currentTime) {
                    // Timeslot has passed, move to next day
                    $startDate = date('Y-m-d', strtotime($startDate . ' +1 day'));
                    if (!isset($usedTimeslotsByDate[$startDate])) {
                        $usedTimeslotsByDate[$startDate] = [];
                    }
                    $attempts++;
                    continue;
                }

                // Timeslot is available
                $selectedDate = $startDate;
                $selectedTimeslot = $timeslot;
                break;
            }

            // Fallback if no timeslot found
            if (!$selectedDate) {
                $selectedDate = date('Y-m-d', strtotime($startDate . ' +1 day'));
                $selectedTimeslot = $times[0];
            }

            return $selectedDate . ' ' . date('H:i:s', strtotime($selectedTimeslot));
        } else {
            // No posts exist, find first available timeslot from today
            $selectedDate = $currentDate;
            $selectedTimeslot = null;

            // Check for available timeslot today
            foreach ($times as $timeslot) {
                $timeslot24Hour = date('H:i:s', strtotime($timeslot));

                // Check if timeslot is available (not passed)
                if ($timeslot24Hour > $currentTime) {
                    $selectedTimeslot = $timeslot;
                    break;
                }
            }

            // If no timeslot available today, use first timeslot tomorrow
            if (!$selectedTimeslot) {
                $selectedDate = date('Y-m-d', strtotime($currentDate . ' +1 day'));
                $selectedTimeslot = $times[0];
            }

            return $selectedDate . ' ' . date('H:i:s', strtotime($selectedTimeslot));
        }
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
            if ($social_type == 'tiktok') {
                $account = Tiktok::findOrFail($account);
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
        return $post ? date("jS M, Y", strtotime($post->publish_date)) : 'NA';
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
                    echo "Image: " . $value . "\n";
                    $image = !empty($value) ? url(getImage('', $value)) : automation_placeholder_image();
                    echo "Image: " . $image . "\n";
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
                                $message_object = isset($response->message) ? $response->message : $response;
                                $message_object = isset($message_object->error) ? $message_object->error : $message_object;
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

    /**
     * Get a user-friendly response message for the post
     * Returns success message if published, or error message if failed
     * 
     * @return string
     */
    public function getResponseMessageAttribute()
    {
        // If post is successfully published
        if ($this->status == 1) {
            $response = $this->response;
            if (!empty($response)) {
                $decoded = json_decode($response, true);
                if (is_array($decoded) && isset($decoded['message'])) {
                    return $decoded['message'];
                }
            }
            // Default success message based on platform
            $platform = ucfirst($this->social_type ?? 'social media');
            return "Post published successfully to {$platform}";
        }

        // If post failed to publish
        if ($this->status == -1) {
            $response = $this->response;
            if (empty($response)) {
                return "Failed to publish post. Please try again.";
            }

            // Try to decode JSON response
            $decoded = json_decode($response, true);

            if (is_array($decoded)) {
                // Check for error message in JSON
                if (isset($decoded['error'])) {
                    $error = $decoded['error'];
                    return $this->formatErrorMessage($error);
                }

                // Check for message field
                if (isset($decoded['message'])) {
                    return $this->formatErrorMessage($decoded['message']);
                }
            }

            // If response is a string, try to format it
            if (is_string($response)) {
                // Check if it's JSON string
                $jsonDecoded = json_decode($response, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($jsonDecoded)) {
                    if (isset($jsonDecoded['error'])) {
                        return $this->formatErrorMessage($jsonDecoded['error']);
                    }
                    if (isset($jsonDecoded['message'])) {
                        return $this->formatErrorMessage($jsonDecoded['message']);
                    }
                }

                // If it's a plain string, format it
                return $this->formatErrorMessage($response);
            }

            // Fallback
            return "Failed to publish post. Please check your account connection and try again.";
        }

        // If post is pending
        return "Post is scheduled and waiting to be published.";
    }

    /**
     * Format error message to be user-friendly
     * Removes technical codes, JSON formatting, and makes it readable
     * 
     * @param mixed $error
     * @return string
     */
    private function formatErrorMessage($error)
    {
        if (is_array($error)) {
            // If error is an array, try to extract message
            if (isset($error['message'])) {
                return $this->cleanErrorMessage($error['message']);
            }
            if (isset($error['error_description'])) {
                return $this->cleanErrorMessage($error['error_description']);
            }
            if (isset($error['error'])) {
                return $this->cleanErrorMessage($error['error']);
            }
            // If it's an array with numeric keys, join them
            return $this->cleanErrorMessage(implode(', ', array_filter($error)));
        }

        if (is_object($error)) {
            // If error is an object, try to get message property
            if (isset($error->message)) {
                return $this->cleanErrorMessage($error->message);
            }
            if (isset($error->error_description)) {
                return $this->cleanErrorMessage($error->error_description);
            }
        }

        // If error is a string, clean it
        return $this->cleanErrorMessage((string) $error);
    }

    /**
     * Clean error message to remove technical details and make it readable
     * 
     * @param string $message
     * @return string
     */
    private function cleanErrorMessage($message)
    {
        if (empty($message)) {
            return "An unknown error occurred. Please try again.";
        }

        $message = trim($message);

        // Remove JSON-like structures
        $message = preg_replace('/\{[^}]*\}/', '', $message);
        $message = preg_replace('/\[[^\]]*\]/', '', $message);

        // Remove common technical prefixes
        $message = preg_replace('/^(Invalid request:\s*)/i', '', $message);
        $message = preg_replace('/^(API error:\s*)/i', '', $message);
        $message = preg_replace('/^(Error\s*\d+:\s*)/i', '', $message);
        $message = preg_replace('/^(Exception:\s*)/i', '', $message);
        $message = preg_replace('/^(Facebook\s*API\s*error:\s*)/i', '', $message);
        $message = preg_replace('/^(Pinterest\s*API\s*error:\s*)/i', '', $message);
        $message = preg_replace('/^(TikTok\s*API\s*error:\s*)/i', '', $message);

        // Remove log IDs and technical codes
        $message = preg_replace('/\s*\[Log\s*ID:\s*[^\]]+\]/i', '', $message);
        $message = preg_replace('/\s*\(Log\s*ID:\s*[^\)]+\)/i', '', $message);
        $message = preg_replace('/\s*\[code:\s*[^\]]+\]/i', '', $message);
        $message = preg_replace('/\s*\(code:\s*[^\)]+\)/i', '', $message);

        // Remove HTTP status codes
        $message = preg_replace('/\s*\d{3}\s*/', ' ', $message);

        // Clean up multiple spaces
        $message = preg_replace('/\s+/', ' ', $message);
        $message = trim($message);

        // If message is still empty or too technical, provide a generic message
        if (empty($message) || strlen($message) < 3) {
            $platform = ucfirst($this->social_type ?? 'social media');
            return "Failed to publish post to {$platform}. Please check your account connection and try again.";
        }

        // Capitalize first letter
        $message = ucfirst($message);

        // Add period if missing
        if (!preg_match('/[.!?]$/', $message)) {
            $message .= '.';
        }

        return $message;
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
        if ($social_type == "tiktok") {
            $account_name = $this->tiktok?->display_name;
        }
        return $account_name;
    }

    /**
     * Parent account username (Facebook/Pinterest username, or TikTok username)
     */
    public function getAccountUsernameAttribute()
    {
        $social_type = $this->social_type;
        if ($social_type === 'facebook') {
            return $this->page?->facebook?->username ?? '';
        }
        if ($social_type === 'pinterest') {
            return $this->board?->pinterest?->username ?? '';
        }
        if ($social_type === 'tiktok') {
            return $this->tiktok?->username ?? $this->tiktok?->display_name ?? '';
        }
        return '';
    }

    public function getAccountProfileAttribute()
    {
        $social_type = $this->social_type;
        $profile_image = '';
        if ($social_type == "facebook") {
            // Use page's profile image if available, otherwise fall back to parent Facebook account's profile image
            if ($this->page && !empty($this->page->profile_image)) {
                $profile_image = $this->page->profile_image;
            } elseif ($this->page?->facebook) {
                $profile_image = $this->page->facebook->profile_image;
            }
        }
        if ($social_type == "pinterest") {
            $profile_image = $this->board?->pinterest ? $this->board->pinterest->profile_image : null;
        }
        return $profile_image;
    }

    public function getPostDetailsAttribute()
    {
        // Use pinterest_post_details for all social types
        $view = view("user.schedule.dataTable.pinterest_post_details")->with("post", $this);
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

    public function getPublishedAtFormattedAttribute()
    {
        if ($this->published_at) {
            return date("Y-m-d h:i A", strtotime($this->published_at));
        }
        return null;
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
