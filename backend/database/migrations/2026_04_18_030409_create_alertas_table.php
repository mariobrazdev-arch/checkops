<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alertas', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->foreignUuid('setor_id')->constrained('setores')->cascadeOnDelete();
            $table->foreignUuid('rotina_id')->constrained('rotinas')->cascadeOnDelete();
            $table->foreignUuid('colaborador_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('gestor_id')->constrained('users')->cascadeOnDelete();
            $table->tinyInteger('falhas_consecutivas')->default(0);
            $table->timestamp('silenciado_ate')->nullable(); // ciente por 7 dias
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alertas');
    }
};
