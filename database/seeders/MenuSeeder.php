<?php

namespace Database\Seeders;

use App\Models\Menu;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $menus = [
            ["id" => "1", "name" => "Schedule", "icon" => "fas fa-calendar", "route" => "panel.schedule", "display_order" => "1"],
            ["id" => "2", "name" => "Automation", "icon" => "fas fa-rss", "route" => "panel.automation", "display_order" => "2"],
            ["id" => "3", "name" => "Api Posts", "icon" => "fas fa-code", "route" => "panel.api-posts", "display_order" => "3"],
            ["id" => "4", "name" => "Accounts", "icon" => "fas fa-user-circle", "route" => "panel.accounts", "display_order" => "4"],
            ["id" => "5", "name" => "Teams", "icon" => "fas fa-users", "route" => "panel.team-members.index", "display_order" => "5"],
        ];

        foreach ($menus as $menu) {
            $checkMenu = Menu::find($menu['id']);
            if (!$checkMenu) {
                Menu::insert($menu);
            }
        }
    }
}
