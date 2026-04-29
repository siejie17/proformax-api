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
        Schema::table('user_answers', function (Blueprint $table) {
            $table->foreignId('selection_group_id')->nullable()->after('item_id')->constrained('selection_groups')->onDelete('cascade');
            $table->foreignId('option_group_id')->nullable()->after('selection_group_id')->constrained('option_groups')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_answers', function (Blueprint $table) {
            $table->dropColumn(['selection_group_id', 'option_group_id']);
        });
    }
};
