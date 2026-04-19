<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // rotinas_diarias: query mais executada do app
        Schema::table('rotinas_diarias', function (Blueprint $table): void {
            $table->index(['colaborador_id', 'data', 'status'], 'rd_colaborador_data_status');
            $table->index(['rotina_id', 'data'],                'rd_rotina_data');
            $table->index(['status', 'data'],                   'rd_status_data');
        });

        // rotinas: listagem por gestor / admin
        Schema::table('rotinas', function (Blueprint $table): void {
            $table->index(['setor_id', 'status'],   'rotinas_setor_status');
            $table->index(['empresa_id', 'status'], 'rotinas_empresa_status');
        });

        // auditoria: filtros de período
        Schema::table('auditoria', function (Blueprint $table): void {
            $table->index(['empresa_id', 'created_at'],  'auditoria_empresa_created');
            $table->index(['usuario_id', 'created_at'],  'auditoria_usuario_created');
        });

        // push_subscriptions: busca por colaborador
        Schema::table('push_subscriptions', function (Blueprint $table): void {
            $table->index('user_id', 'push_subscriptions_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('rotinas_diarias', function (Blueprint $table): void {
            $table->dropIndex('rd_colaborador_data_status');
            $table->dropIndex('rd_rotina_data');
            $table->dropIndex('rd_status_data');
        });

        Schema::table('rotinas', function (Blueprint $table): void {
            $table->dropIndex('rotinas_setor_status');
            $table->dropIndex('rotinas_empresa_status');
        });

        Schema::table('auditoria', function (Blueprint $table): void {
            $table->dropIndex('auditoria_empresa_created');
            $table->dropIndex('auditoria_usuario_created');
        });

        Schema::table('push_subscriptions', function (Blueprint $table): void {
            $table->dropIndex('push_subscriptions_user_id');
        });
    }
};
