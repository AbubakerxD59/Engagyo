<?php

namespace App\Http\Controllers;

use App\Models\ShortLink;
use Illuminate\Http\RedirectResponse;

class ShortLinkRedirectController extends Controller
{
    /**
     * Redirect from shortened link to original URL.
     */
    public function __invoke(string $code): RedirectResponse
    {
        $shortLink = ShortLink::where('short_code', $code)->first();

        if (!$shortLink) {
            abort(404, 'Short link not found.');
        }

        $shortLink->increment('clicks');

        return redirect()->away($shortLink->original_url, 302);
    }
}
