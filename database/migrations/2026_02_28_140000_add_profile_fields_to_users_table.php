<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('external_id')->nullable()->unique()->after('id');
            $table->string('first_name')->nullable()->after('email');
            $table->string('last_name')->nullable()->after('first_name');
            $table->string('username')->nullable()->after('last_name');
            $table->enum('gender', ['male', 'female', 'other'])->nullable()->after('username');
            $table->date('date_of_birth')->nullable()->after('gender');
            $table->string('nationality', 10)->nullable()->after('date_of_birth');
            $table->string('picture_large')->nullable()->after('nationality');
            $table->string('picture_thumbnail')->nullable()->after('picture_large');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'external_id', 'first_name', 'last_name', 'username',
                'gender', 'date_of_birth', 'nationality',
                'picture_large', 'picture_thumbnail',
            ]);
        });
    }
};
