<?php

use App\Models\InstagramAccount;
use App\Models\Page;
use App\Models\TeamMember;
use App\Models\TeamMemberAccount;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE team_member_accounts MODIFY COLUMN account_type ENUM('page', 'board', 'tiktok', 'instagram') NOT NULL");
        }

        if (! Schema::hasTable('team_member_accounts') || ! Schema::hasTable('instagram_accounts')) {
            return;
        }

        TeamMemberAccount::query()
            ->where('account_type', 'page')
            ->orderBy('id')
            ->chunkById(100, function ($rows) {
                foreach ($rows as $row) {
                    $page = Page::withoutGlobalScopes()->find($row->account_id);
                    if (! $page) {
                        continue;
                    }
                    $member = TeamMember::find($row->team_member_id);
                    if (! $member) {
                        continue;
                    }
                    $instagramRows = InstagramAccount::query()
                        ->where('user_id', $member->team_lead_id)
                        ->where('page_id', (string) $page->page_id)
                        ->pluck('id');
                    foreach ($instagramRows as $igId) {
                        TeamMemberAccount::firstOrCreate(
                            [
                                'team_member_id' => $row->team_member_id,
                                'account_type' => 'instagram',
                                'account_id' => $igId,
                            ],
                            []
                        );
                    }
                }
            });
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        TeamMemberAccount::query()->where('account_type', 'instagram')->delete();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE team_member_accounts MODIFY COLUMN account_type ENUM('page', 'board', 'tiktok') NOT NULL");
        }
    }
};
