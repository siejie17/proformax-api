<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assessment_review_id')->unique()->constrained('assessment_reviews')->cascadeOnDelete();
            $table->string('certificate_number', 40)->unique();
            $table->uuid('verification_code')->unique();
            $table->string('certification_level', 100);
            $table->unsignedInteger('approved_actual_score');
            $table->unsignedInteger('maximum_score');
            $table->string('status', 24)->default('issued')->index();
            $table->json('project_snapshot');
            $table->foreignId('issued_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('issued_at');
            $table->timestamp('valid_until')->nullable();
            $table->foreignId('revoked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('revoked_at')->nullable();
            $table->text('revocation_reason')->nullable();
            $table->unsignedSmallInteger('template_version')->default(1);
            $table->string('pdf_path', 500)->nullable();
            $table->char('pdf_sha256', 64)->nullable();
            $table->timestamps();

            $table->index(['project_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_certificates');
    }
};
