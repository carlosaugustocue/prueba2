<?php

namespace App\Modules\AdminConfig\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Affiliates\Models\Eps;
use App\Modules\SocialSecurity\Models\Afp;
use App\Modules\SocialSecurity\Models\Arp;
use App\Modules\SocialSecurity\Models\Ccf;
use App\Modules\SocialSecurity\Models\ClientType;
use App\Modules\SocialSecurity\Models\ContributorType;
use App\Modules\SocialSecurity\Models\NoveltyType;
use App\Modules\SocialSecurity\Models\AccountingRegistry;
use App\Modules\SocialSecurity\Models\PaymentOperator;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CatalogController extends Controller
{
    private const CATALOGS = [
        'eps' => [
            'model' => Eps::class,
            'label' => 'EPS',
            'label_plural' => 'EPS',
            'route_prefix' => 'admin.configuracion.eps',
        ],
        'afps' => [
            'model' => Afp::class,
            'label' => 'AFP',
            'label_plural' => 'AFPs',
            'route_prefix' => 'admin.configuracion.afps',
        ],
        'arps' => [
            'model' => Arp::class,
            'label' => 'ARP',
            'label_plural' => 'ARPs',
            'route_prefix' => 'admin.configuracion.arps',
        ],
        'ccfs' => [
            'model' => Ccf::class,
            'label' => 'CCF',
            'label_plural' => 'CCFs',
            'route_prefix' => 'admin.configuracion.ccfs',
        ],
        'operadores-pago' => [
            'model' => PaymentOperator::class,
            'label' => 'Operador de pago',
            'label_plural' => 'Operadores de pago',
            'route_prefix' => 'admin.configuracion.operadores-pago',
        ],
        'client-types' => [
            'model' => ClientType::class,
            'label' => 'Tipo de cliente',
            'label_plural' => 'Tipos de cliente',
            'route_prefix' => 'admin.configuracion.client-types',
        ],
        'contributor-types' => [
            'model' => ContributorType::class,
            'label' => 'Tipo de cotizante',
            'label_plural' => 'Tipos de cotizante',
            'route_prefix' => 'admin.configuracion.contributor-types',
        ],
        'novelty-types' => [
            'model' => NoveltyType::class,
            'label' => 'Tipo de novedad',
            'label_plural' => 'Tipos de novedad',
            'route_prefix' => 'admin.configuracion.novelty-types',
        ],
        'accounting-registries' => [
            'model' => AccountingRegistry::class,
            'label' => 'Registro contable',
            'label_plural' => 'Registros contables',
            'route_prefix' => 'admin.configuracion.accounting-registries',
        ],
    ];

    public function index(): Response
    {
        $catalogs = [
            ['key' => 'eps', 'label' => 'EPS', 'description' => 'Entidades promotoras de salud', 'href' => route('admin.configuracion.eps.index')],
            ['key' => 'afps', 'label' => 'AFPs', 'description' => 'Fondos de pensiones', 'href' => route('admin.configuracion.afps.index')],
            ['key' => 'arps', 'label' => 'ARPs', 'description' => 'Administradoras de riesgo laboral', 'href' => route('admin.configuracion.arps.index')],
            ['key' => 'ccfs', 'label' => 'CCFs', 'description' => 'Cajas de compensación familiar', 'href' => route('admin.configuracion.ccfs.index')],
            ['key' => 'operadores-pago', 'label' => 'Operadores de pago', 'description' => 'Operadores para pagos de seguridad social', 'href' => route('admin.configuracion.operadores-pago.index')],
            ['key' => 'client-types', 'label' => 'Tipos de cliente', 'description' => 'INDEPENDENT, DEPENDENT, SERVICONLI, FOREIGN_RESIDENT', 'href' => route('admin.configuracion.client-types.index')],
            ['key' => 'contributor-types', 'label' => 'Tipos de cotizante', 'description' => 'Catálogo PILA (01–59)', 'href' => route('admin.configuracion.contributor-types.index')],
            ['key' => 'novelty-types', 'label' => 'Tipos de novedad', 'description' => 'Ingreso, Retiro, Traslado EPS', 'href' => route('admin.configuracion.novelty-types.index')],
            ['key' => 'accounting-registries', 'label' => 'Registros contables', 'description' => 'RECIBO_CAJA, FACTURA_ELECTRONICA', 'href' => route('admin.configuracion.accounting-registries.index')],
            ['key' => 'risk-classes', 'label' => 'Clases de riesgo ARL', 'description' => 'Niveles 0–5 y tarifas % (normativa colombiana)', 'href' => route('admin.configuracion.risk-classes.index')],
            ['key' => 'contribution-parameters', 'label' => 'Parámetros de aportes', 'description' => 'Porcentajes, SMLMV, vigencia normativa', 'href' => route('admin.configuracion.contribution-parameters.index')],
        ];

        return Inertia::render('Admin/Config/Index', [
            'catalogs' => $catalogs,
        ]);
    }

    public function catalogIndex(Request $request): Response
    {
        $type = $request->route()->parameter('type');
        $config = $this->configFor($type);
        $query = $config['model']::query();
        $query->orderBy($type === 'contributor-types' ? 'code' : 'name');
        $items = $query->get(['id', 'name', 'code', 'is_active']);

        return Inertia::render('Admin/Config/CatalogIndex', [
            'type' => $type,
            'label' => $config['label_plural'],
            'items' => $items,
            'routePrefix' => $config['route_prefix'],
        ]);
    }

    public function catalogCreate(Request $request): Response
    {
        $type = $request->route()->parameter('type');
        $config = $this->configFor($type);

        return Inertia::render('Admin/Config/CatalogForm', [
            'type' => $type,
            'label' => $config['label'],
            'labelPlural' => $config['label_plural'],
            'item' => null,
            'routePrefix' => $config['route_prefix'],
        ]);
    }

    public function catalogStore(Request $request): RedirectResponse
    {
        $type = $request->route()->parameter('type');
        $config = $this->configFor($type);
        $codeRules = $type === 'contributor-types'
            ? ['required', 'string', 'max:10', 'unique:contributor_types,code']
            : ['nullable', 'string', 'max:50'];
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'code' => $codeRules,
            'is_active' => ['boolean'],
        ]);
        $validated['is_active'] = $request->boolean('is_active', true);

        $config['model']::create($validated);

        return redirect()->route("{$config['route_prefix']}.index")
            ->with('success', "{$config['label']} creado correctamente.");
    }

    public function catalogEdit(Request $request, int $id): Response|RedirectResponse
    {
        $type = $request->route()->parameter('type');
        $config = $this->configFor($type);
        $item = $config['model']::find($id);
        if (! $item) {
            return redirect()->route("{$config['route_prefix']}.index")->with('error', 'Registro no encontrado.');
        }

        return Inertia::render('Admin/Config/CatalogForm', [
            'type' => $type,
            'label' => $config['label'],
            'labelPlural' => $config['label_plural'],
            'item' => $item->only(['id', 'name', 'code', 'is_active']),
            'routePrefix' => $config['route_prefix'],
        ]);
    }

    public function catalogUpdate(Request $request, int $id): RedirectResponse
    {
        $type = $request->route()->parameter('type');
        $config = $this->configFor($type);
        $item = $config['model']::find($id);
        if (! $item) {
            return redirect()->route("{$config['route_prefix']}.index")->with('error', 'Registro no encontrado.');
        }

        $codeRules = $type === 'contributor-types'
            ? ['required', 'string', 'max:10', \Illuminate\Validation\Rule::unique('contributor_types', 'code')->ignore($id)]
            : ['nullable', 'string', 'max:50'];
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'code' => $codeRules,
            'is_active' => ['boolean'],
        ]);
        $validated['is_active'] = $request->boolean('is_active', true);

        $item->update($validated);

        return redirect()->route("{$config['route_prefix']}.index")
            ->with('success', "{$config['label']} actualizado correctamente.");
    }

    public function catalogDestroy(Request $request, int $id): RedirectResponse
    {
        $type = $request->route()->parameter('type');
        $config = $this->configFor($type);
        $item = $config['model']::find($id);
        if (! $item) {
            return redirect()->route("{$config['route_prefix']}.index")->with('error', 'Registro no encontrado.');
        }
        $item->delete();

        return redirect()->route("{$config['route_prefix']}.index")
            ->with('success', "{$config['label']} eliminado correctamente.");
    }

    private function configFor(string $type): array
    {
        if (! isset(self::CATALOGS[$type])) {
            abort(404, 'Catálogo no válido.');
        }
        return self::CATALOGS[$type];
    }
}
