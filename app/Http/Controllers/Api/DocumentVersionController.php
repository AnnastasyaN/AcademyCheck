<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\TextExtractionException;
use App\Http\Controllers\Api\Traits\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\DocumentVersion;
use App\Services\DocumentVersionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class DocumentVersionController extends Controller
{
    use ApiResponse;

    public function __construct(
        private DocumentVersionService $documentVersionService,
    ) {}

    public function index(Request $request, Document $document): JsonResponse
    {
        if (! $this->canAccess($request, $document)) {
            return $this->forbidden('Anda tidak memiliki akses ke dokumen ini.');
        }

        return $this->success(
            $document->versions()
                ->withCount('analysisResults')
                ->orderBy('version_number')
                ->get(),
        );
    }

    public function store(Request $request, Document $document): JsonResponse
    {
        if ($document->user_id !== $request->user()->id) {
            return $this->forbidden('Anda tidak memiliki akses untuk mengunggah revisi dokumen ini.');
        }

        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:pdf,docx', 'max:10240'],
            'revision_note' => ['nullable', 'string', 'max:5000'],
        ]);

        try {
            $version = $this->documentVersionService->createRevision(
                $document,
                $request->file('file'),
                $validated['revision_note'] ?? null,
            );
        } catch (TextExtractionException $exception) {
            report($exception);

            return $this->validationError(null, $exception->getMessage());
        } catch (Throwable $exception) {
            throw $exception;
        }

        return $this->created([
            'version' => $version,
            'extracted_text_preview' => mb_substr($version->extracted_text, 0, 500),
        ], 'Versi revisi berhasil diunggah.');
    }

    private function canAccess(Request $request, Document $document): bool
    {
        return $document->user_id === $request->user()->id || $request->user()->isAdmin();
    }
}
