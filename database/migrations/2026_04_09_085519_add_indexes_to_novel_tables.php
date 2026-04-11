<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chapters', function (Blueprint $table) {
            $table->index('order');
        });

        Schema::table('source_documents', function (Blueprint $table) {
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::table('chapters', function (Blueprint $table) {
            $table->dropIndex(['order']);
        });

        Schema::table('source_documents', function (Blueprint $table) {
            $table->dropIndex(['status']);
        });
    }
};
