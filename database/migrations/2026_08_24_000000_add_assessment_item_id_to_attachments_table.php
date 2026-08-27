<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attachments', function (Blueprint $table) {
            $table->foreignId('assessment_item_id')
                ->nullable()
                ->after('user_id')
                ->constrained('items')
                ->nullOnDelete();
            $table->index(['project_id', 'assessment_item_id']);
        });
    }

    public function down(): void
    {
        Schema::table('attachments', function (Blueprint $table) {
            $table->dropIndex(['project_id', 'assessment_item_id']);
            $table->dropConstrainedForeignId('assessment_item_id');
        });
    }
};
