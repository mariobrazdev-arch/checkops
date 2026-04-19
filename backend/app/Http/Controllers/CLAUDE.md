# Controllers

## Regra de ouro
Controller **não** contém lógica de negócio. Apenas:
1. Recebe request (via FormRequest)
2. Chama Service ou Model
3. Retorna response

## Padrão de nomenclatura
`{Perfil}{Recurso}Controller` → `AdminRotinasController`, `GestorDashboardController`

## Template padrão
```php
class AdminRotinasController extends Controller
{
    public function __construct(private RotinaService $service) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'data' => RotinaResource::collection(
                $this->service->listar($request->user(), $request->validated())
            )
        ]);
    }

    public function store(StoreRotinaRequest $request): JsonResponse
    {
        $rotina = $this->service->criar($request->user(), $request->validated());
        return response()->json(['data' => new RotinaResource($rotina)], 201);
    }
}
```

## Controllers existentes por perfil
```
admin/
  AdminEmpresaController
  AdminSetoresController
  AdminUsuariosController
  AdminRotinasController
  AdminAuditoriaController
  AdminDashboardController
  AdminRelatoriosController

gestor/
  GestorSetorController
  GestorColaboradoresController
  GestorRotinasController
  GestorDashboardController
  GestorValidacaoController

colaborador/
  ColaboradorRotinasController
  ColaboradorHistoricoController

shared/
  AuthController
  PerfilController
```
