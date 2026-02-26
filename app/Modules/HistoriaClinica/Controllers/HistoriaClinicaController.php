<?php

namespace App\Modules\HistoriaClinica\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\HistoriaClinica\Models\DocumentoClinico;
use App\Modules\HistoriaClinica\Models\EncuentroClinico;
use App\Modules\HistoriaClinica\Models\ExamenFisico;
use App\Modules\HistoriaClinica\Requests\StoreAntecedenteRequest;
use App\Modules\HistoriaClinica\Requests\StoreDocumentoClinicoRequest;
use App\Modules\HistoriaClinica\Requests\StoreEncuentroClinicoRequest;
use App\Modules\HistoriaClinica\Requests\StoreExamenFisicoRequest;
use App\Modules\HistoriaClinica\Resources\EncuentroClinicoResource;
use App\Modules\HistoriaClinica\Resources\HistoriaClinicaResource;
use App\Modules\HistoriaClinica\Services\AuditoriaHcService;
use App\Modules\HistoriaClinica\Services\HistoriaClinicaService;
use App\Modules\Patients\Models\Affiliate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class HistoriaClinicaController extends Controller
{
    public function __construct(
        protected HistoriaClinicaService $historiaClinicaService
    ) {}

    /**
     * Muestra la historia clínica del afiliado (crea si no existe). Solo roles atencion, admin.
     */
    public function show(Affiliate $affiliate): Response
    {
        $hc = $this->historiaClinicaService->getOrCreateForAffiliate($affiliate);
        $hc->load(['encuentros', 'documentos', 'affiliate:id,uuid,first_name,second_name,last_name,second_last_name,document_number']);

        AuditoriaHcService::logRead('historia_clinica', (string) $hc->id);

        return Inertia::render('HistoriaClinica/Show', [
            'historiaClinica' => new HistoriaClinicaResource($hc),
            'tiposAtencion' => \App\Modules\HistoriaClinica\Enums\TipoAtencion::toArray(),
            'tiposDocumento' => \App\Modules\HistoriaClinica\Enums\TipoDocumentoClinico::toArray(),
        ]);
    }

    /**
     * Registra un nuevo encuentro clínico.
     */
    public function storeEncuentro(StoreEncuentroClinicoRequest $request, Affiliate $affiliate): RedirectResponse
    {
        $hc = $this->historiaClinicaService->getOrCreateForAffiliate($affiliate);
        $encuentro = $hc->encuentros()->create([
            'tipo_atencion' => $request->input('tipo_atencion'),
            'fecha_atencion' => $request->input('fecha_atencion'),
            'motivo_consulta' => $request->input('motivo_consulta'),
            'enfermedad_actual' => $request->input('enfermedad_actual'),
            'estado_mental' => $request->input('estado_mental'),
            'profesional_id' => $request->input('profesional_id') ?: auth()->id(),
        ]);

        AuditoriaHcService::logCreate('encuentros_clinicos', (string) $encuentro->id, $encuentro->toArray());

        return redirect()
            ->route('affiliates.historia-clinica.show', $affiliate)
            ->with('success', 'Encuentro clínico registrado.');
    }

    /**
     * Sube un documento clínico (almacenamiento privado).
     */
    public function storeDocumento(StoreDocumentoClinicoRequest $request, Affiliate $affiliate): RedirectResponse
    {
        $hc = $this->historiaClinicaService->getOrCreateForAffiliate($affiliate);

        $file = $request->file('file');
        $path = $file->store(
            "historia-clinica/{$hc->id}",
            'local'
        );

        $doc = $hc->documentos()->create([
            'tipo' => $request->input('tipo'),
            'nombre_archivo' => $file->getClientOriginalName(),
            'ruta_almacenamiento' => $path,
            'fecha_documento' => $request->input('fecha_documento'),
            'uploaded_by' => auth()->id(),
        ]);

        AuditoriaHcService::logCreate('documentos_clinicos', (string) $doc->id, [
            'nombre_archivo' => $doc->nombre_archivo,
            'tipo' => $doc->tipo?->value,
        ]);

        return redirect()
            ->route('affiliates.historia-clinica.show', $affiliate)
            ->with('success', 'Documento subido correctamente.');
    }

    /**
     * Descarga un documento (solo si pertenece a la HC del afiliado). Registra auditoría READ.
     */
    public function downloadDocumento(Affiliate $affiliate, DocumentoClinico $documento): StreamedResponse|RedirectResponse
    {
        $hc = $this->historiaClinicaService->getOrCreateForAffiliate($affiliate);
        if ($documento->historia_clinica_id !== $hc->id) {
            abort(404);
        }

        if (! Storage::disk('local')->exists($documento->ruta_almacenamiento)) {
            return redirect()
                ->route('affiliates.historia-clinica.show', $affiliate)
                ->with('error', 'El archivo no existe en el almacenamiento.');
        }

        AuditoriaHcService::logRead('documentos_clinicos', (string) $documento->id);

        return Storage::disk('local')->download(
            $documento->ruta_almacenamiento,
            $documento->nombre_archivo,
            ['Content-Type' => 'application/octet-stream']
        );
    }

    /**
     * Detalle de un encuentro clínico: datos del encuentro, antecedentes (HC) y examen físico.
     */
    public function showEncuentro(Affiliate $affiliate, EncuentroClinico $encuentro): Response|RedirectResponse
    {
        $hc = $this->historiaClinicaService->getOrCreateForAffiliate($affiliate);
        if ($encuentro->historia_clinica_id !== $hc->id) {
            abort(404);
        }

        $encuentro->load(['examenFisico', 'historiaClinica.affiliate:id,uuid,first_name,second_name,last_name,second_last_name,document_number']);
        $hc->load(['antecedentes']);

        AuditoriaHcService::logRead('encuentros_clinicos', (string) $encuentro->id);

        return Inertia::render('HistoriaClinica/EncuentroShow', [
            'encuentro' => new EncuentroClinicoResource($encuentro),
            'historiaClinica' => new HistoriaClinicaResource($hc),
            'antecedentes' => \App\Modules\HistoriaClinica\Resources\AntecedenteResource::collection($hc->antecedentes),
            'tiposAntecedente' => \App\Modules\HistoriaClinica\Enums\TipoAntecedente::toArray(),
        ]);
    }

    /**
     * Registra un antecedente en la historia clínica (inmutable una vez registrado).
     */
    public function storeAntecedente(StoreAntecedenteRequest $request, Affiliate $affiliate): RedirectResponse
    {
        $hc = $this->historiaClinicaService->getOrCreateForAffiliate($affiliate);
        $antecedente = $hc->antecedentes()->create([
            'tipo' => $request->input('tipo'),
            'descripcion' => $request->input('descripcion'),
            'fecha_registro' => $request->input('fecha_registro'),
            'profesional_id' => $request->input('profesional_id') ?: auth()->id(),
        ]);

        AuditoriaHcService::logCreate('antecedentes', (string) $antecedente->id, $antecedente->toArray());

        $encuentroId = $request->input('encuentro_id');
        if ($encuentroId) {
            return redirect()
                ->route('affiliates.historia-clinica.encuentros.show', [$affiliate, $encuentroId])
                ->with('success', 'Antecedente registrado.');
        }

        return redirect()
            ->route('affiliates.historia-clinica.show', $affiliate)
            ->with('success', 'Antecedente registrado.');
    }

    /**
     * Crea o actualiza el examen físico del encuentro (uno por encuentro).
     */
    public function storeExamenFisico(StoreExamenFisicoRequest $request, Affiliate $affiliate, EncuentroClinico $encuentro): RedirectResponse
    {
        $hc = $this->historiaClinicaService->getOrCreateForAffiliate($affiliate);
        if ($encuentro->historia_clinica_id !== $hc->id) {
            abort(404);
        }

        $data = $request->validated();
        $peso = isset($data['peso_kg']) ? (float) $data['peso_kg'] : null;
        $talla = isset($data['talla_cm']) ? (float) $data['talla_cm'] : null;
        if ($peso !== null && $talla !== null) {
            $data['imc'] = ExamenFisico::calcularImc($peso, $talla);
        }

        $examen = $encuentro->examenFisico;
        if ($examen) {
            $datosAnteriores = $examen->toArray();
            $examen->update($data);
            AuditoriaHcService::logUpdate('examenes_fisicos', (string) $examen->id, $datosAnteriores, $examen->fresh()->toArray());
        } else {
            $examen = $encuentro->examenFisico()->create($data);
            AuditoriaHcService::logCreate('examenes_fisicos', (string) $examen->id, $examen->toArray());
        }

        return redirect()
            ->route('affiliates.historia-clinica.encuentros.show', [$affiliate, $encuentro])
            ->with('success', 'Examen físico guardado.');
    }
}
