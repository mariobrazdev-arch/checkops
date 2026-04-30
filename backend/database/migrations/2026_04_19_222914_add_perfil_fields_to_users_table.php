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
        Schema::table('users', function (Blueprint $table) {
            $table->string('cpf', 14)->nullable()->after('telefone');
            $table->string('sexo', 20)->nullable()->after('cpf');
            $table->date('data_nascimento')->nullable()->after('sexo');
            $table->string('foto_perfil_path')->nullable()->after('data_nascimento');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['cpf', 'sexo', 'data_nascimento', 'foto_perfil_path']);
        });
    }
};
