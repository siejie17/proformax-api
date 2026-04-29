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
        Schema::table('options', function (Blueprint $table) {
            $table->foreignId('classification_id')
                ->nullable()
                ->constrained('building_classifications')
                ->nullOnDelete();
            $table->foreignId('option_group_id')->nullable()->constrained('option_groups')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('options', function (Blueprint $table) {
            $table->dropForeign(['classification_id']);
            $table->dropForeign(['option_group_id']);
        });
    }
};
