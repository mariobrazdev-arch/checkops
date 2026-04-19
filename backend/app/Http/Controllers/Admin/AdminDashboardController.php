<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminDashboardController extends Controller
{
    public function __construct(private readonly DashboardService $service) {}

    /**
     * US-18 — Dashboard consolidado para admin.
     */
    public function index(Request $request): JsonResponse
    {
        $dados = $this->service->admin($request->user(), $request->only(['periodo', 'setor_id']));

        return response()->json(['data' => $dados]);
    }
}

