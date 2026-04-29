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
        Schema::create('actual_user_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('project_id')->constrained('projects')->onDelete('cascade');
            $table->foreignId('item_id')->nullable()->constrained('items')->onDelete('set null');
            $table->foreignId('option_group_id')->nullable()->constrained('option_groups')->onDelete('set null');
            $table->foreignId('option_id')->nullable()->constrained('options')->onDelete('set null');
            $table->foreignId('selection_group_id')->nullable()->constrained('selection_groups')->onDelete('set null');
            $table->foreignId('selection_id')->nullable()->constrained('selections')->onDelete('set null');
            $table->foreignId('subitem_id')->nullable()->constrained('subitems')->onDelete('set null');
            $table->string('custom_answer')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('actual_user_answers');
    }
};
