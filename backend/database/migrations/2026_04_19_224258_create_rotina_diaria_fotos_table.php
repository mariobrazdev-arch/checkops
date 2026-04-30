<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rotina_diaria_fotos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('rotina_diaria_id')->constrained('rotinas_diarias')->cascadeOnDelete();
            $table->string('path');
            $table->unsignedSmallInteger('ordem')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rotina_diaria_fotos');
    }
};
