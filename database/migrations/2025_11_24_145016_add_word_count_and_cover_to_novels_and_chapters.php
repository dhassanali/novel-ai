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
        Schema::table('chapters', function (Blueprint $table) {
            $table->unsignedInteger('word_count')->default(0)->after('content');
        });

        Schema::table('novels', function (Blueprint $table) {
            $table->string('cover_image')->nullable()->after('genre');
            $table->unsignedInteger('total_word_count')->default(0)->after('cover_image');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chapters', function (Blueprint $table) {
            $table->dropColumn('word_count');
        });

        Schema::table('novels', function (Blueprint $table) {
            $table->dropColumn(['cover_image', 'total_word_count']);
        });
    }
};
