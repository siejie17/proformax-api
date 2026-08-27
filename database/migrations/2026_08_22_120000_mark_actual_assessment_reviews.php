<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assessment_item_reviews', function (Blueprint $table) {
            $table->string('review_basis', 24)->default('legacy_predicted')->after('reviewed_score')->index();
        });

        Schema::table('assessment_reviews', function (Blueprint $table) {
            $table->unsignedInteger('approved_actual_total')->nullable()->after('new_status');
            $table->string('certification_level', 100)->nullable()->after('approved_actual_total');
        });
    }

    public function down(): void
    {
        Schema::table('assessment_reviews', function (Blueprint $table) {
            $table->dropColumn(['approved_actual_total', 'certification_level']);
        });

        Schema::table('assessment_item_reviews', function (Blueprint $table) {
            $table->dropIndex(['review_basis']);
            $table->dropColumn('review_basis');
        });
    }
};
