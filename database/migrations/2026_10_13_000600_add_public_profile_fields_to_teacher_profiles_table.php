<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teacher_profiles', function (Blueprint $table) {
            $table->string('photo_path')->nullable();
            $table->text('bio')->nullable();
            $table->boolean('is_public')->default(false)->index();
            $table->unsignedInteger('public_sort_order')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('teacher_profiles', function (Blueprint $table) {
            $table->dropIndex(['is_public']);
            $table->dropColumn(['photo_path', 'bio', 'is_public', 'public_sort_order']);
        });
    }
};
