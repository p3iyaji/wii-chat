<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Update existing messages to have type 'text'
        DB::table('chat_messages')
            ->whereNull('type')
            ->update(['type' => 'text']);

        // Make sure the column has default value
        Schema::table('chat_messages', function (Blueprint $table) {
            $table->string('type')->default('text')->change();
        });
    }

    public function down(): void
    {
        // Revert if needed
        Schema::table('chat_messages', function (Blueprint $table) {
            $table->string('type')->nullable()->change();
        });
    }
};