<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FetchedLink extends Model
{
    use HasFactory;
    protected $table = "fetched_links";
    protected $fillable = [
        "domain",
        "path",
        "title",
        "image_link",
        "pin_image_link",
    ];
}
