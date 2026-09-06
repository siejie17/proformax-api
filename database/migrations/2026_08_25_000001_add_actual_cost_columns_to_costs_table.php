<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('costs', function (Blueprint $table) {
            if (!Schema::hasColumn('costs', 'actual_cost')) {
                $table->decimal('actual_cost', 15, 2)->default(0)->after('item_cost');
            }
            if (!Schema::hasColumn('costs', 'actual_pct')) {
                $table->decimal('actual_pct', 8, 2)->default(0)->after('actual_cost');
            }
            if (!Schema::hasColumn('costs', 'actual_direction')) {
                $table->string('actual_direction', 10)->default('up')->after('actual_pct');
            }
        });
    }

    public function down(): void
    {
        Schema::table('costs', function (Blueprint $table) {
            if (Schema::hasColumn('costs', 'actual_direction')) {
                $table->dropColumn('actual_direction');
            }
            if (Schema::hasColumn('costs', 'actual_pct')) {
                $table->dropColumn('actual_pct');
            }
            if (Schema::hasColumn('costs', 'actual_cost')) {
                $table->dropColumn('actual_cost');
            }
        });
    }
};
