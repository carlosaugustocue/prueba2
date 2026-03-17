<?php

namespace App\Modules\PilaManagement\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\PilaManagement\Models\PilaEmployer;
use App\Modules\PilaManagement\Requests\StorePilaEmployerRequest;
use App\Modules\PilaManagement\Requests\UpdatePilaEmployerRequest;
use App\Modules\PilaManagement\Resources\PilaEmployerResource;
use App\Modules\PilaManagement\Services\DeadlineService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PilaEmployerController extends Controller
{
    public function __construct(private DeadlineService $deadlineService) {}

    public function index(Request $request): Response
    {
        $query = PilaEmployer::query()->orderBy('name');

        if ($request->filled('search')) {
            $term = $request->input('search');
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('document_number', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%");
            });
        }

        if ($request->has('is_active')) {
            if ($request->input('is_active') === '1') {
                $query->where('is_active', true);
            } elseif ($request->input('is_active') === '0') {
                $query->where('is_active', false);
            }
        }

        $employers = $query->paginate($request->integer('per_page', 15))->withQueryString();

        return Inertia::render('PilaEmployers/Index', [
            'employers' => PilaEmployerResource::collection($employers),
            'filters' => $request->only(['search', 'is_active']),
            'allowedDocumentTypes' => config('pila.employer.allowed_document_types', []),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('PilaEmployers/Create', [
            'allowedDocumentTypes' => config('pila.employer.allowed_document_types', []),
        ]);
    }

    public function store(StorePilaEmployerRequest $request): RedirectResponse
    {
        $employer = PilaEmployer::create($request->validated());

        return redirect()->route('employers.show', $employer)
            ->with('success', 'Empleador registrado correctamente.');
    }

    public function show(PilaEmployer $employer, Request $request): Response
    {
        $year = $request->integer('year', (int) now()->format('Y'));
        $month = $request->integer('month', (int) now()->format('n'));

        $paymentDay = $employer->payment_business_day
            ?? $this->deadlineService->paymentBusinessDayFromDocument($employer->document_number);

        $dueDate = $this->deadlineService->dueDateForPeriodByPaymentDay($year, $month, (int) $paymentDay);

        return Inertia::render('PilaEmployers/Show', [
            'employer' => new PilaEmployerResource($employer),
            'dueDate' => $dueDate->toDateString(),
            'period' => ['year' => $year, 'month' => $month],
            'paymentBusinessDay' => (int) $paymentDay,
        ]);
    }

    public function edit(PilaEmployer $employer): Response
    {
        return Inertia::render('PilaEmployers/Edit', [
            'employer' => new PilaEmployerResource($employer),
            'allowedDocumentTypes' => config('pila.employer.allowed_document_types', []),
        ]);
    }

    public function update(UpdatePilaEmployerRequest $request, PilaEmployer $employer): RedirectResponse
    {
        $employer->update($request->validated());

        return redirect()->route('employers.show', $employer)
            ->with('success', 'Empleador actualizado correctamente.');
    }

    public function destroy(PilaEmployer $employer): RedirectResponse
    {
        // Sprint futuro: evitar borrar si tiene afiliaciones/credenciales asociadas.
        $employer->delete();

        return redirect()->route('employers.index')->with('success', 'Empleador eliminado correctamente.');
    }
}

