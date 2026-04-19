<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rotina_colaboradores', function (Blueprint $table) {
            $table->uuid('rotina_id');
            $table->uuid('colaborador_id');

            $table->foreign('rotina_id')->references('id')->on('rotinas')->cascadeOnDelete();
            $table->foreign('colaborador_id')->references('id')->on('users')->cascadeOnDelete();

            $table->primary(['rotina_id', 'colaborador_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rotina_colaboradores');
    }
};
