<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rotinas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('empresa_id')->constrained('empresas');
            $table->foreignUuid('setor_id')->constrained('setores');
            $table->string('titulo');
            $table->text('descricao')->nullable();
            $table->enum('frequencia', ['diaria', 'semanal', 'mensal', 'turno']);
            $table->json('dias_semana')->nullable();
            $table->json('dias_mes')->nullable();
            $table->time('horario_previsto');
            $table->boolean('foto_obrigatoria')->default(false);
            $table->boolean('so_camera')->default(true);
            $table->boolean('justif_obrigatoria')->default(false);
            $table->enum('status', ['ativa', 'inativa'])->default('ativa');
            $table->date('data_inicio');
            $table->date('data_fim')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rotinas');
    }
};
