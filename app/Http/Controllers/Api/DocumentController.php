<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\TextExtractionException;
use App\Http\Controllers\Api\Traits\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\DocumentType;
use App\Services\TextExtractionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Throwable;

class DocumentController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $documents = Document::query()
            ->with(['documentType', 'latestVersion'])
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate(15);

        return $this->paginated($documents);
    }

    public function store(Request $request, TextExtractionService $textExtractionService): JsonResponse
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

        $storedPath = null;

        try {
            [$document, $version] = DB::transaction(function () use ($request, $validated, $textExtractionService, &$storedPath): array {
                $document = Document::create([
                    'user_id' => $request->user()->id,
                    'document_type_id' => $validated['document_type_id'],
                    'title' => $validated['title'],
                    'topic' => $validated['topic'] ?? null,
                    'keywords' => $validated['keywords'] ?? null,
                    'description' => $validated['description'] ?? null,
                    'status' => Document::STATUS_UPLOADED,
                ]);

                $file = $request->file('file');
                $storedPath = $file->store(
                    "documents/user_{$request->user()->id}/document_{$document->id}",
                    'local',
                );

                if ($storedPath === false) {
                    throw new \RuntimeException('Dokumen gagal disimpan.');
                }

                $extractedText = $textExtractionService->extract(
                    Storage::disk('local')->path($storedPath),
                    $file->getClientOriginalExtension(),
                );

                $version = $document->versions()->create([
                    'version_number' => 1,
                    'file_path' => $storedPath,
                    'file_original_name' => $file->getClientOriginalName(),
                    'file_type' => strtolower($file->getClientOriginalExtension()),
                    'file_size' => $file->getSize(),
                    'extracted_text' => $extractedText,
                    'uploaded_at' => now(),
                ]);

                $document->update([
                    'latest_version_id' => $version->id,
                ]);

                return [$document, $version];
            });
        } catch (TextExtractionException $exception) {
            report($exception);

            if ($storedPath !== null) {
                Storage::disk('local')->delete($storedPath);
            }

            return $this->validationError(null, $exception->getMessage());
        } catch (Throwable $exception) {
            if ($storedPath !== null) {
                Storage::disk('local')->delete($storedPath);
            }

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
        ]));
    }

    public function update(Request $request, Document $document, TextExtractionService $textExtractionService): JsonResponse
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
            'file' => ['sometimes', 'required', 'file', 'mimes:pdf,docx', 'max:10240'],
        ]);

        $storedPath = null;

        try {
            DB::transaction(function () use ($request, $document, $validated, $textExtractionService, &$storedPath): void {
                $document->update($validated);

                if ($request->hasFile('file')) {
                    $file = $request->file('file');
                    $storedPath = $file->store(
                        "documents/user_{$document->user_id}/document_{$document->id}",
                        'local',
                    );

                    if ($storedPath === false) {
                        throw new \RuntimeException('Dokumen gagal disimpan.');
                    }

                    $extractedText = $textExtractionService->extract(
                        Storage::disk('local')->path($storedPath),
                        $file->getClientOriginalExtension(),
                    );

                    $nextVersion = ($document->versions()->max('version_number') ?? 0) + 1;

                    $version = $document->versions()->create([
                        'version_number' => $nextVersion,
                        'file_path' => $storedPath,
                        'file_original_name' => $file->getClientOriginalName(),
                        'file_type' => strtolower($file->getClientOriginalExtension()),
                        'file_size' => $file->getSize(),
                        'extracted_text' => $extractedText,
                        'uploaded_at' => now(),
                    ]);

                    $document->update([
                        'latest_version_id' => $version->id,
                        'status' => Document::STATUS_UPLOADED,
                    ]);
                }
            });
        } catch (TextExtractionException $exception) {
            report($exception);

            if ($storedPath !== null) {
                Storage::disk('local')->delete($storedPath);
            }

            return $this->validationError(null, $exception->getMessage());
        } catch (Throwable $exception) {
            if ($storedPath !== null) {
                Storage::disk('local')->delete($storedPath);
            }

            Log::error('Gagal memperbarui dokumen: ' . $exception->getMessage());

            throw $exception;
        }

        return $this->success(
            $document->fresh(['documentType', 'latestVersion', 'versions']),
            $storedPath
                ? 'Dokumen berhasil diperbarui dan file baru disimpan sebagai versi terbaru.'
                : 'Dokumen berhasil diperbarui.',
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
