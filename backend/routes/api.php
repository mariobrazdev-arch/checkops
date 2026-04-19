<?php

use App\Http\Controllers\Admin\AdminAuditoriaController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminEmpresaController;
use App\Http\Controllers\Admin\AdminRelatoriosController;
use App\Http\Controllers\Admin\AdminRotinasController;
use App\Http\Controllers\Admin\AdminSetoresController;
use App\Http\Controllers\Admin\AdminUsuariosController;
use App\Http\Controllers\Colaborador\ColaboradorHistoricoController;
use App\Http\Controllers\Colaborador\ColaboradorRotinasController;
use App\Http\Controllers\Gestor\GestorAlertasController;
use App\Http\Controllers\Gestor\GestorColaboradoresController;
use App\Http\Controllers\Gestor\GestorDashboardController;
use App\Http\Controllers\Gestor\GestorRotinasController;
use App\Http\Controllers\Gestor\GestorSetorController;
use App\Http\Controllers\Gestor\GestorValidacaoController;
use App\Http\Controllers\PushSubscriptionController;
use App\Http\Controllers\Shared\AuthController;
use App\Http\Controllers\Shared\PerfilController;
use App\Http\Controllers\SuperAdmin\SuperAdminEmpresasController;
use App\Http\Controllers\SuperAdmin\SuperAdminUsuariosController;
use App\Http\Controllers\SuperAdmin\SuperAdminPlanosController;
use Illuminate\Support\Facades\Route;

// ─── Rotas públicas ────────────────────────────────────────────────────────
Route::prefix('v1')->group(function () {

    // Auth — rotas públicas (rate limit: 10/min por IP)
    Route::prefix('auth')->middleware('throttle:login')->group(function () {
        Route::post('/login', [AuthController::class, 'login']);
        Route::post('/esqueci-senha', [AuthController::class, 'esqueciSenha']);
        Route::post('/redefinir-senha', [AuthController::class, 'redefinirSenha']);
    });

    // ─── Autenticado (todos os perfis) ────────────────────────────────────
    Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {

        // Auth — rotas protegidas
        Route::prefix('auth')->group(function () {
            Route::post('/logout', [AuthController::class, 'logout']);
            Route::get('/me', [AuthController::class, 'me']);
            Route::get('/perfil', [PerfilController::class, 'show']);
            Route::put('/perfil', [PerfilController::class, 'update']);
        });

        // Push subscriptions — qualquer perfil autenticado (US-20)
        Route::post('/push/subscribe',   [PushSubscriptionController::class, 'store']);
        Route::delete('/push/unsubscribe', [PushSubscriptionController::class, 'destroy']);

        // ─── Super Admin ──────────────────────────────────────────────────
        Route::middleware('perfil:super_admin')->prefix('super-admin')->group(function () {
            Route::apiResource('/empresas', SuperAdminEmpresasController::class);
            Route::apiResource('/usuarios', SuperAdminUsuariosController::class);
            Route::apiResource('/planos',   SuperAdminPlanosController::class);
        });

        // ─── Admin ────────────────────────────────────────────────────────
        Route::middleware('perfil:admin')->prefix('admin')->group(function () {

            // Empresa
            Route::get('/empresa', [AdminEmpresaController::class, 'show']);
            Route::put('/empresa', [AdminEmpresaController::class, 'update']);

            // Setores
            Route::apiResource('/setores', AdminSetoresController::class);

            // Usuários
            Route::apiResource('/usuarios', AdminUsuariosController::class);

            // Rotinas
            Route::apiResource('/rotinas', AdminRotinasController::class);
            Route::get('/rotinas/{rotina}/preview', [AdminRotinasController::class, 'preview']);

            // Dashboard (US-18)
            Route::get('/dashboard', [AdminDashboardController::class, 'index']);

            // Relatórios (US-19)
            Route::get('/relatorios',                    [AdminRelatoriosController::class, 'index']);
            Route::post('/relatorios/exportar',          [AdminRelatoriosController::class, 'exportar']);
            Route::get('/relatorios/status/{jobId}',     [AdminRelatoriosController::class, 'status']);

            // Auditoria (US-16)
            Route::get('/auditoria',           [AdminAuditoriaController::class, 'index']);
            Route::get('/auditoria/exportar',  [AdminAuditoriaController::class, 'exportar']);
        });

        // ─── Gestor ───────────────────────────────────────────────────────
        Route::middleware('perfil:gestor,admin')->prefix('gestor')->group(function () {

            Route::get('/setor', [GestorSetorController::class, 'show']);
            Route::put('/setor', [GestorSetorController::class, 'update']);

            Route::apiResource('/colaboradores', GestorColaboradoresController::class);
            Route::apiResource('/rotinas', GestorRotinasController::class);
            Route::get('/rotinas/{rotina}/preview', [GestorRotinasController::class, 'preview']);
            Route::post('/rotinas/{rotinaDiaria}/reabrir', [GestorRotinasController::class, 'reabrir']);

            // Dashboard (US-17)
            Route::get('/dashboard', [GestorDashboardController::class, 'index']);

            // Validação (US-14)
            Route::get('/validacoes', [GestorValidacaoController::class, 'index']);
            Route::get('/validacoes/{rotinaDiaria}', [GestorValidacaoController::class, 'show']);
            Route::post('/validacoes/{rotinaDiaria}/reabrir', [GestorValidacaoController::class, 'reabrir']);

            // Alertas (US-21)
            Route::get('/alertas', [GestorAlertasController::class, 'index']);
            Route::post('/alertas/{id}/ciente', [GestorAlertasController::class, 'marcarCiente']);
        });

        // ─── Colaborador ──────────────────────────────────────────────────
        Route::middleware('perfil:colaborador,gestor,admin')->prefix('colaborador')->group(function () {

            // ATENÇÃO: rotas literais ANTES das com parâmetro {rotinaDiaria}
            Route::get('/rotinas/hoje', [ColaboradorRotinasController::class, 'hoje']);
            Route::get('/rotinas/historico', [ColaboradorHistoricoController::class, 'index']); // US-15

            // Upload com rate limit próprio (30/min)
            Route::middleware('throttle:foto')->group(function () {
                Route::post('/rotinas/{rotinaDiaria}/sim', [ColaboradorRotinasController::class, 'responderSim']);
            });
            Route::post('/rotinas/{rotinaDiaria}/nao', [ColaboradorRotinasController::class, 'responderNao']);
        });
    });
});
