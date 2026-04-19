<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('empresa_id')->constrained('empresas');
            // setor_id adicionado após create_setores_table (dependência circular)
            $table->uuid('setor_id')->nullable();
            $table->uuid('gestor_id')->nullable();
            $table->string('nome');
            $table->string('email');
            $table->string('matricula')->nullable();
            $table->string('cargo')->nullable();
            $table->enum('perfil', ['admin', 'gestor', 'colaborador']);
            $table->enum('status', ['ativo', 'inativo'])->default('ativo');
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['empresa_id', 'email']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
