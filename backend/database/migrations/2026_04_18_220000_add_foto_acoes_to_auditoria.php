<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // PostgreSQL não permite ALTER COLUMN de CHECK diretamente — drop + recreate
        DB::statement('ALTER TABLE auditoria DROP CONSTRAINT IF EXISTS auditoria_acao_check');
        DB::statement("ALTER TABLE auditoria ADD CONSTRAINT auditoria_acao_check CHECK (acao IN (
            'login','logout','criar','editar','excluir','responder','upload_foto','reabrir',
            'sem_gps','alerta_foto_antiga'
        ))");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE auditoria DROP CONSTRAINT IF EXISTS auditoria_acao_check');
        DB::statement("ALTER TABLE auditoria ADD CONSTRAINT auditoria_acao_check CHECK (acao IN (
            'login','logout','criar','editar','excluir','responder','upload_foto','reabrir'
        ))");
    }
};
