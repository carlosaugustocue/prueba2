<?php

namespace App\Modules\SocialSecurity\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Patients\Models\Affiliate;
use App\Modules\SocialSecurity\Models\SupportDocument;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SupportDocumentController extends Controller
{
    private const DISK = 'local';
    private const DIRECTORY = 'support_documents';

    public function store(Request $request, Affiliate $affiliate): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'document' => ['required', 'file', 'max:20480'], // 20 MB
        ]);

        $file = $request->file('document');
        $path = $file->store(
            self::DIRECTORY . '/' . $affiliate->id,
            self::DISK
        );

        SupportDocument::create([
            'affiliate_id' => $affiliate->id,
            'payroll_id' => $request->input('payroll_id'),
            'title' => $validated['title'],
            'file_path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
        ]);

        return redirect()->back()->with('success', 'Documento subido correctamente.');
    }

    public function download(Affiliate $affiliate, SupportDocument $supportDocument): StreamedResponse
    {
        if ($supportDocument->affiliate_id !== $affiliate->id) {
            abort(404);
        }

        if (!Storage::disk(self::DISK)->exists($supportDocument->file_path)) {
            abort(404, 'Archivo no encontrado.');
        }

        $name = $supportDocument->original_name ?: $supportDocument->title . '.' . pathinfo($supportDocument->file_path, PATHINFO_EXTENSION);

        return Storage::disk(self::DISK)->download(
            $supportDocument->file_path,
            $name,
            ['Content-Type' => $supportDocument->mime_type]
        );
    }

    public function destroy(Affiliate $affiliate, SupportDocument $supportDocument): RedirectResponse
    {
        if ($supportDocument->affiliate_id !== $affiliate->id) {
            abort(404);
        }

        Storage::disk(self::DISK)->delete($supportDocument->file_path);
        $supportDocument->delete();

        return redirect()->back()->with('success', 'Documento eliminado.');
    }
}
