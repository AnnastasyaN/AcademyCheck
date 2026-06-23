<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\TextExtractionException;
use App\Http\Controllers\Api\Traits\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\DocumentType;
use App\Services\DocumentVersionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Throwable;

class DocumentController extends Controller
{
    use ApiResponse;

    public function __construct(
        private DocumentVersionService $documentVersionService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $documents = Document::query()
            ->with(['documentType', 'latestVersion'])
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get();

        return $this->success($documents);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'document_type_id' => [
                'required',
                Rule::exists(DocumentType::class, 'id')->where('is_active', true),
            ],
            'title' => ['required', 'string', 'max:255'],
            'topic' => ['nullable', 'string', 'max:255'],
            'keywords' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'file' => ['required', 'file', 'mimes:pdf,docx', 'max:10240'],
        ]);

        try {
            [$document, $version] = DB::transaction(function () use ($request, $validated): array {
                $document = Document::create([
                    'user_id' => $request->user()->id,
                    'document_type_id' => $validated['document_type_id'],
                    'title' => $validated['title'],
                    'topic' => $validated['topic'] ?? null,
                    'keywords' => $validated['keywords'] ?? null,
                    'description' => $validated['description'] ?? null,
                    'status' => Document::STATUS_UPLOADED,
                ]);

                $version = $this->documentVersionService->createInitialVersion(
                    $document,
                    $request->file('file'),
                );

                return [$document, $version];
            });
        } catch (TextExtractionException $exception) {
            report($exception);

            return $this->validationError(null, $exception->getMessage());
        } catch (Throwable $exception) {
            throw $exception;
        }

        return $this->created([
            'document' => $document->load('documentType'),
            'version' => $version,
            'extracted_text_preview' => mb_substr($version->extracted_text, 0, 500),
        ], 'Dokumen berhasil diunggah dan teks berhasil diekstrak.');
    }

    public function show(Request $request, Document $document): JsonResponse
    {
        if ($document->user_id !== $request->user()->id && ! $request->user()->isAdmin()) {
            return $this->forbidden('Anda tidak memiliki akses ke dokumen ini.');
        }

        return $this->success($document->load([
            'documentType',
            'versions',
            'latestVersion',
            'analysisResults.aspectScores',
        ])->loadCount('reviewerComments'));
    }

    public function update(Request $request, Document $document): JsonResponse
    {
        if ($document->user_id !== $request->user()->id) {
            return $this->forbidden('Anda tidak memiliki akses untuk memperbarui dokumen ini.');
        }

        $validated = $request->validate([
            'document_type_id' => [
                'sometimes',
                'required',
                Rule::exists(DocumentType::class, 'id')->where('is_active', true),
            ],
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'topic' => ['sometimes', 'nullable', 'string', 'max:255'],
            'keywords' => ['sometimes', 'nullable', 'string'],
            'description' => ['sometimes', 'nullable', 'string'],
        ]);

        $document->update($validated);

        return $this->success(
            $document->fresh(['documentType']),
            'Dokumen berhasil diperbarui.',
        );
    }

    public function destroy(Request $request, Document $document): JsonResponse
    {
        if ($document->user_id !== $request->user()->id) {
            return $this->forbidden('Anda tidak memiliki akses untuk menghapus dokumen ini.');
        }

        $paths = $document->versions()
            ->pluck('file_path')
            ->filter()
            ->values()
            ->all();

        DB::transaction(fn () => $document->delete());

        if ($paths !== []) {
            Storage::disk('local')->delete($paths);
        }

        return $this->success(null, 'Dokumen berhasil dihapus.');
    }
}
