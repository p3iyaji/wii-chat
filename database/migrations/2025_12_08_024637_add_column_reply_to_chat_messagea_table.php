<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('chat_messages', function (Blueprint $table) {
            if (!Schema::hasColumn('chat_messages', 'reply_to')) {
                $table->foreignId('reply_to')
                    ->nullable()
                    ->after('body')
                    ->constrained('chat_messages')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('chat_messages', function (Blueprint $table) {
            if (Schema::hasColumn('chat_messages', 'deleted_for_user_id')) {
                $table->dropForeign(['deleted_for_user_id']);
                $table->dropColumn('deleted_for_user_id');
            }
        });
    }

};
