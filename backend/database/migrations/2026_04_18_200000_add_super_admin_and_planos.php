<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Planos
        Schema::create('planos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nome');
            $table->integer('limite_usuarios')->default(10);
            $table->integer('limite_setores')->default(5);
            $table->integer('limite_rotinas')->default(50);
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });

        // plano_id em empresas
        Schema::table('empresas', function (Blueprint $table) {
            $table->foreignUuid('plano_id')->nullable()->constrained('planos')->nullOnDelete();
        });

        // empresa_id nullable em users (super_admin não tem empresa)
        Schema::table('users', function (Blueprint $table) {
            $table->foreignUuid('empresa_id')->nullable()->change();
        });

        // adiciona super_admin ao enum perfil
        DB::statement("ALTER TABLE users DROP CONSTRAINT IF EXISTS users_perfil_check");
        DB::statement("ALTER TABLE users ALTER COLUMN perfil TYPE VARCHAR(20)");
        DB::statement("ALTER TABLE users ADD CONSTRAINT users_perfil_check CHECK (perfil IN ('super_admin','admin','gestor','colaborador'))");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE users DROP CONSTRAINT IF EXISTS users_perfil_check");
        DB::statement("ALTER TABLE users ADD CONSTRAINT users_perfil_check CHECK (perfil IN ('admin','gestor','colaborador'))");
        Schema::table('empresas', fn($t) => $t->dropForeign(['plano_id']));
        Schema::table('empresas', fn($t) => $t->dropColumn('plano_id'));
        Schema::dropIfExists('planos');
    }
};
