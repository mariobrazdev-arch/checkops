<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('auditoria', function (Blueprint $table) {
            $table->dropForeign(['empresa_id']);
            $table->uuid('empresa_id')->nullable()->change();
            $table->foreign('empresa_id')->references('id')->on('empresas')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('auditoria', function (Blueprint $table) {
            $table->dropForeign(['empresa_id']);
            $table->uuid('empresa_id')->nullable(false)->change();
            $table->foreignUuid('empresa_id')->constrained('empresas');
        });
    }
};
