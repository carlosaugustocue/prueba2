<?php

namespace App\Modules\Patients\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Patients\Models\Affiliate;
use App\Modules\Patients\Models\AffiliatePayment;
use App\Modules\SocialSecurity\Models\AccountingRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AffiliatePaymentController extends Controller
{
    /**
     * Página dedicada: listar y registrar pagos del afiliado (Cartera / Seguridad Social).
     */
    public function index(Affiliate $affiliate): Response
    {
        $payments = $affiliate->payments()
            ->with('accountingRegistry:id,name,code')
            ->orderByDesc('payment_date')
            ->orderByDesc('id')
            ->get()
            ->map(fn (AffiliatePayment $p) => [
                'id' => $p->id,
                'payment_date' => $p->payment_date?->format('Y-m-d'),
                'payment_date_formatted' => $p->payment_date?->format('d/m/Y'),
                'amount' => (float) $p->amount,
                'external_number' => $p->external_number,
                'description' => $p->description,
                'accounting_registry' => $p->accountingRegistry ? [
                    'id' => $p->accountingRegistry->id,
                    'name' => $p->accountingRegistry->name,
                    'code' => $p->accountingRegistry->code,
                ] : null,
            ]);

        $accountingRegistries = AccountingRegistry::active()
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        return Inertia::render('Affiliates/Payments', [
            'affiliate' => [
                'id' => $affiliate->id,
                'full_name' => $affiliate->full_name,
                'document_number' => $affiliate->document_number,
            ],
            'payments' => $payments,
            'accountingRegistries' => $accountingRegistries,
        ]);
    }

    /**
     * Registra un nuevo pago para el afiliado (usado por Cartera y Seguridad Social).
     */
    public function store(Request $request, Affiliate $affiliate): RedirectResponse
    {
        $data = $request->validate([
            'payment_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'external_number' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
            'accounting_registry_id' => ['nullable', 'exists:accounting_registries,id'],
            'payroll_id' => ['nullable', 'exists:payrolls,id'],
        ]);

        AffiliatePayment::create([
            'affiliate_id' => $affiliate->id,
            'payroll_id' => $data['payroll_id'] ?? null,
            'accounting_registry_id' => $data['accounting_registry_id'] ?? null,
            'payment_date' => $data['payment_date'],
            'amount' => $data['amount'],
            'external_number' => $data['external_number'] ?? null,
            'description' => $data['description'] ?? null,
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('affiliates.payments.index', $affiliate)
            ->with('success', 'Pago registrado correctamente.');
    }
}

