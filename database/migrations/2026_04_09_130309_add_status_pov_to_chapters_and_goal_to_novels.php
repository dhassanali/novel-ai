<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chapters', function (Blueprint $table) {
            $table->string('status')->default('draft')->after('order');
            $table->foreignId('pov_character_id')->nullable()->constrained('characters')->nullOnDelete()->after('status');
        });

        Schema::table('novels', function (Blueprint $table) {
            $table->unsignedInteger('word_count_goal')->nullable()->after('total_word_count');
        });
    }

    public function down(): void
    {
        Schema::table('chapters', function (Blueprint $table) {
            $table->dropForeign(['pov_character_id']);
            $table->dropColumn(['status', 'pov_character_id']);
        });

        Schema::table('novels', function (Blueprint $table) {
            $table->dropColumn('word_count_goal');
        });
    }
};
