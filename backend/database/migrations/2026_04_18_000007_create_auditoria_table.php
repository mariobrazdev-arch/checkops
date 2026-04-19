<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auditoria', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignUuid('empresa_id')->constrained('empresas');
            $table->foreignUuid('usuario_id')->constrained('users');
            $table->enum('acao', ['login', 'logout', 'criar', 'editar', 'excluir', 'responder', 'upload_foto', 'reabrir']);
            $table->string('entidade');
            $table->uuid('entidade_id')->nullable();
            $table->json('dados_antes')->nullable();
            $table->json('dados_depois')->nullable();
            $table->string('ip', 45);
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auditoria');
    }
};
