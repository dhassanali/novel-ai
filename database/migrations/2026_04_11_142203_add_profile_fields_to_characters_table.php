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
        Schema::table('characters', function (Blueprint $table) {
            $table->text('personality_traits')->nullable()->after('role');
            $table->text('backstory')->nullable()->after('personality_traits');
            $table->text('goals')->nullable()->after('backstory');
            $table->text('speech_patterns')->nullable()->after('goals');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('characters', function (Blueprint $table) {
            $table->dropColumn(['personality_traits', 'backstory', 'goals', 'speech_patterns']);
        });
    }
};
