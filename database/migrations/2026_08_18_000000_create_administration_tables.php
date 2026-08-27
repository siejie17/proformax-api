<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('system_role', 32)->default('user')->after('role_id')->index();
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->string('assessment_status', 40)->default('submitted')->index();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_remarks')->nullable();
        });

        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('system_role', 32)->nullable();
            $table->string('action', 100)->index();
            $table->string('target_type', 100)->nullable();
            $table->unsignedBigInteger('target_id')->nullable();
            $table->string('target_label')->nullable();
            $table->string('status', 32)->default('success');
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['target_type', 'target_id']);
        });

        Schema::create('facilitator_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('appointed_by')->constrained('users')->cascadeOnDelete();
            $table->string('status', 24)->default('active')->index();
            $table->timestamp('appointed_at');
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
            $table->unique(['project_id', 'user_id']);
        });

        Schema::create('assessment_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('action', 32);
            $table->string('previous_status', 40);
            $table->string('new_status', 40);
            $table->text('remarks')->nullable();
            $table->timestamps();
        });

        Schema::create('recommendations', function (Blueprint $table) {
            $table->id();
            $table->string('certification_level', 80)->unique();
            $table->string('title');
            $table->text('content');
            $table->boolean('is_active')->default(true)->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('admin_references', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('category', 100)->nullable()->index();
            $table->string('file_url', 2048)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_references');
        Schema::dropIfExists('recommendations');
        Schema::dropIfExists('assessment_reviews');
        Schema::dropIfExists('facilitator_assignments');
        Schema::dropIfExists('activity_logs');

        Schema::table('projects', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropColumn(['assessment_status', 'reviewed_at', 'review_remarks']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('system_role');
        });
    }
};
