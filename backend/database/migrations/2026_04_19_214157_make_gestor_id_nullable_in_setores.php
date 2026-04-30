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
        Schema::table('setores', function (Blueprint $table) {
            $table->dropForeign(['gestor_id']);
            $table->uuid('gestor_id')->nullable()->change();
            $table->foreign('gestor_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('setores', function (Blueprint $table) {
            $table->dropForeign(['gestor_id']);
            $table->uuid('gestor_id')->nullable(false)->change();
            $table->foreignUuid('gestor_id')->constrained('users');
        });
    }
};
