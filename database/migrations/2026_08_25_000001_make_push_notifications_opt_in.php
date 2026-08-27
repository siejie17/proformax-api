<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')
            ->whereNotExists(fn ($query) => $query
                ->selectRaw('1')
                ->from('push_subscriptions')
                ->whereColumn('push_subscriptions.user_id', 'users.id'))
            ->update(['push_notifications' => false]);

        Schema::table('users', function (Blueprint $table) {
            $table->boolean('push_notifications')->default(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('push_notifications')->default(true)->change();
        });
    }
};
