<?php

namespace App\Services;

use App\Models\ShortLink;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;

class ShrtLnkClickSyncService
{
    public function __construct(
        protected ShrtLnkApiService $shrtLnkApi
    ) {}

    /**
     * @return array{synced: int, unchanged: int, failed: int, not_found: int, skipped: int}
     */
    public function syncAll(): array
    {
        if (! $this->shrtLnkApi->isEnabled()) {
            return [
                'synced' => 0,
                'unchanged' => 0,
                'failed' => 0,
                'not_found' => 0,
                'skipped' => 0,
            ];
        }

        $summary = [
            'synced' => 0,
            'unchanged' => 0,
            'failed' => 0,
            'not_found' => 0,
            'skipped' => 0,
        ];

        $chunkSize = max(1, (int) config('shrtlnk.sync_chunk', 100));

        $this->shrtLnkLinksQuery()
            ->orderBy('id')
            ->chunkById($chunkSize, function ($links) use (&$summary) {
                foreach ($links as $link) {
                    $result = $this->syncLink($link);
                    $summary[$result]++;
                }
            });

        return $summary;
    }

    /**
     * @return 'synced'|'unchanged'|'failed'|'not_found'|'skipped'
     */
    public function syncLink(ShortLink $link): string
    {
        $code = trim((string) $link->short_code);
        if ($code === '') {
            return 'skipped';
        }

        $response = $this->shrtLnkApi->getLink($code);

        if (! $response['success']) {
            if (! empty($response['not_found'])) {
                return 'not_found';
            }

            Log::info('ShrtLnk click sync failed for link', [
                'short_link_id' => $link->id,
                'short_code' => $code,
                'message' => $response['message'] ?? 'Unknown error',
            ]);

            return 'failed';
        }

        $data = $response['data'] ?? [];
        $clicks = isset($data['clicks']) ? (int) $data['clicks'] : null;

        if ($clicks === null) {
            return 'failed';
        }

        $updates = [];

        if ((int) $link->clicks !== $clicks) {
            $updates['clicks'] = $clicks;
        }

        if (empty($link->shrtlnk_id) && ! empty($data['id'])) {
            $updates['shrtlnk_id'] = (int) $data['id'];
        }

        if (empty($link->short_url) && ! empty($data['short_url'])) {
            $updates['short_url'] = (string) $data['short_url'];
        }

        if ($updates === []) {
            return 'unchanged';
        }

        $link->forceFill($updates)->save();

        return 'synced';
    }

    protected function shrtLnkLinksQuery(): Builder
    {
        $host = strtolower((string) parse_url(config('shrtlnk.base_url'), PHP_URL_HOST));

        return ShortLink::query()
            ->whereNotNull('short_code')
            ->where('short_code', '!=', '')
            ->where(function (Builder $query) use ($host) {
                $query->whereNotNull('shrtlnk_id');

                if ($host !== '') {
                    $query->orWhere('short_url', 'like', '%'.$host.'%');
                }
            });
    }
}
