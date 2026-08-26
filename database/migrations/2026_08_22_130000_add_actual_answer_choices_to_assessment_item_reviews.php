<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assessment_item_reviews', function (Blueprint $table) {
            $table->json('accepted_actual_answer_ids')->nullable()->after('review_basis');
        });
    }

    public function down(): void
    {
        Schema::table('assessment_item_reviews', function (Blueprint $table) {
            $table->dropColumn('accepted_actual_answer_ids');
        });
    }
};
