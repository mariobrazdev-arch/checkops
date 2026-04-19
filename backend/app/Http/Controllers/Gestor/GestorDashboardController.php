<?php

namespace App\Http\Controllers\Gestor;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GestorDashboardController extends Controller
{
    public function __construct(private readonly DashboardService $service) {}

    /**
     * US-17 — Indicadores do setor para o gestor.
     */
    public function index(Request $request): JsonResponse
    {
        $dados = $this->service->gestor($request->user(), $request->only(['periodo']));

        return response()->json(['data' => $dados]);
    }
}

