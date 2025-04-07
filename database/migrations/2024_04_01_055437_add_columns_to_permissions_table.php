<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('permissions', function (Blueprint $table) {
            $table->string('label', 250)->after('id');
            $table->integer('parent_id')->after('guard_name')->nullable();
            $table->tinyInteger('show_on_menu')->after('parent_id')->default('0')->nullable();
            $table->string('route_name', 200)->after('show_on_menu')->nullable();
            $table->string('icon', 200)->after('route_name')->nullable();
            $table->string('tool_tip', 250)->after('icon')->nullable();
            $table->integer('sort_order')->after('tool_tip')->nullable();
            $table->integer('role_id')->after('sort_order')->nullable();
            $table->integer('created_by')->after('role_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('permissions', function (Blueprint $table) {
            $table->dropColumn('label');
            $table->dropColumn('parent_id');
            $table->dropColumn('show_on_menu');
            $table->dropColumn('route_name');
            $table->dropColumn('icon');
            $table->dropColumn('tool_tip');
            $table->dropColumn('sort_order');
            $table->dropColumn('role_id');
            $table->dropColumn('created_by');
        });
    }
};
