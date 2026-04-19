<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rotinas_diarias', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('rotina_id')->constrained('rotinas');
            $table->foreignUuid('colaborador_id')->constrained('users');
            $table->date('data');
            $table->enum('status', ['pendente', 'realizada', 'nao_realizada', 'atrasada'])->default('pendente');
            $table->timestamp('data_hora_resposta')->nullable();
            $table->text('justificativa')->nullable();
            $table->string('foto_url')->nullable();
            $table->decimal('foto_lat', 10, 8)->nullable();
            $table->decimal('foto_lng', 11, 8)->nullable();
            $table->timestamp('foto_timestamp')->nullable();
            $table->string('foto_device_id')->nullable();
            $table->uuid('reaberta_por')->nullable();
            $table->text('reaberta_justificativa')->nullable();
            $table->timestamps();

            $table->unique(['rotina_id', 'colaborador_id', 'data']);
            $table->foreign('reaberta_por')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rotinas_diarias');
    }
};
