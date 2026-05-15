<?php

namespace App\Jobs;

use App\Models\Board;
use App\Services\PinterestService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class DeletePinterestPostJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public function __construct(
        private string $pinId,
        private int $boardId
    ) {}

    public function handle(PinterestService $pinterestService): void
    {
        $board = Board::with('pinterest')->find($this->boardId);
        if (! $board || empty($this->pinId)) {
            return;
        }

        $post = new \App\Models\Post([
            'post_id' => $this->pinId,
            'account_id' => $board->id,
        ]);
        $post->setRelation('board', $board);

        if (! $pinterestService->delete($post)) {
            Log::warning('DeletePinterestPostJob: API delete failed or unsupported', [
                'pin_id' => $this->pinId,
                'board_id' => $this->boardId,
            ]);
        }
    }
}
