<?php

namespace App\Modules\AdminConfig\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\PilaManagement\Models\PilaRiskClass;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * CRUD de clases de riesgo ARL (tabla pila_risk_classes).
 * Normativa colombiana: niveles 0 (No aplica) a 5 (Riesgo máximo) con tarifas en %.
 */
class ArlRiskClassController extends Controller
{
    public function index(Request $request): Response
    {
        $items = PilaRiskClass::query()
            ->orderBy('level')
            ->get(['id', 'level', 'class_name', 'description', 'rate', 'is_active']);

        return Inertia::render('Admin/Config/RiskClasses/Index', [
            'items' => $items,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Config/RiskClasses/Form', [
            'item' => null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'level' => ['required', 'integer', 'min:0', 'max:5', 'unique:pila_risk_classes,level'],
            'class_name' => ['nullable', 'string', 'max:5'],
            'description' => ['required', 'string', 'max:100'],
            'rate_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'is_active' => ['boolean'],
        ]);

        $validated['rate'] = (float) $validated['rate_percent'] / 100;
        $validated['is_active'] = $request->boolean('is_active', true);
        unset($validated['rate_percent']);

        PilaRiskClass::create($validated);

        return redirect()->route('admin.configuracion.risk-classes.index')
            ->with('success', 'Clase de riesgo ARL creada correctamente.');
    }

    public function edit(PilaRiskClass $riskClass): Response|RedirectResponse
    {
        $riskClass->load([]);

        return Inertia::render('Admin/Config/RiskClasses/Form', [
            'item' => [
                'id' => $riskClass->id,
                'level' => $riskClass->level,
                'class_name' => $riskClass->class_name,
                'description' => $riskClass->description,
                'rate_percent' => $riskClass->rate !== null ? (float) $riskClass->rate * 100 : 0,
                'is_active' => (bool) $riskClass->is_active,
            ],
        ]);
    }

    public function update(Request $request, PilaRiskClass $riskClass): RedirectResponse
    {
        $validated = $request->validate([
            'level' => ['required', 'integer', 'min:0', 'max:5', 'unique:pila_risk_classes,level,' . $riskClass->id],
            'class_name' => ['nullable', 'string', 'max:5'],
            'description' => ['required', 'string', 'max:100'],
            'rate_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'is_active' => ['boolean'],
        ]);

        $validated['rate'] = (float) $validated['rate_percent'] / 100;
        $validated['is_active'] = $request->boolean('is_active', true);
        unset($validated['rate_percent']);

        $riskClass->update($validated);

        return redirect()->route('admin.configuracion.risk-classes.index')
            ->with('success', 'Clase de riesgo ARL actualizada correctamente.');
    }

    public function destroy(PilaRiskClass $riskClass): RedirectResponse
    {
        $riskClass->delete();

        return redirect()->route('admin.configuracion.risk-classes.index')
            ->with('success', 'Clase de riesgo ARL eliminada correctamente.');
    }
}
