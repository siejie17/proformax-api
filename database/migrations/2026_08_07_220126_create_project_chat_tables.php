<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── project_members ────────────────────────────────────────────────
        Schema::create('project_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('added_by')->constrained('users');
            $table->enum('role', ['viewer', 'member', 'editor'])->default('member');
            $table->foreignId('last_read_message_id')->nullable();
            $table->timestamps();

            $table->unique(['project_id', 'user_id']);
        });

        // ── attachments ────────────────────────────────────────────────────
        Schema::create('attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained();
            $table->string('original_name');
            $table->string('filename');
            $table->string('path');
            $table->string('mime_type', 100)->nullable();
            $table->enum('kind', ['image', 'pdf', 'spreadsheet']);
            $table->unsignedBigInteger('size')->default(0);
            $table->timestamp('uploaded_at')->useCurrent();

            $table->index(['project_id']);
            $table->index(['user_id']);
        });

        // ── project_messages ───────────────────────────────────────────────
        Schema::create('project_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained();
            $table->text('body');
            $table->foreignId('attachment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('reply_to_id')->nullable()->constrained('project_messages')->nullOnDelete();
            $table->boolean('is_system')->default(false);
            $table->softDeletes();
            $table->timestamps();

            $table->index(['project_id', 'created_at']);
        });

        Schema::table('project_members', function (Blueprint $table) {
            $table->foreign('last_read_message_id')
                ->references('id')
                ->on('project_messages')
                ->cascadeOnDelete();
        });

        // ── message_reactions ──────────────────────────────────────────────
        Schema::create('message_reactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('message_id')
                ->constrained('project_messages')
                ->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('emoji', 16);
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['project_id', 'message_id', 'user_id', 'emoji'], 'reactions_p_m_u_e_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_reactions');
        Schema::dropIfExists('project_messages');
        Schema::dropIfExists('attachments');
        Schema::dropIfExists('project_members');
    }
};
