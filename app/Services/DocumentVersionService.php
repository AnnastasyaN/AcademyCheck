<?php

namespace App\Services;

use App\Exceptions\TextExtractionException;
use App\Models\Document;
use App\Models\DocumentVersion;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class DocumentVersionService
{
    public function __construct(
        private TextExtractionService $textExtractionService,
    ) {}

    public function createInitialVersion(Document $document, UploadedFile $file): DocumentVersion
    {
        $storedPath = $file->store(
            "documents/user_{$document->user_id}/document_{$document->id}",
            'local',
        );

        if ($storedPath === false) {
            throw new RuntimeException('Dokumen gagal disimpan.');
        }

        try {
            $extractedText = $this->textExtractionService->extract(
                Storage::disk('local')->path($storedPath),
                $file->getClientOriginalExtension(),
            );
        } catch (TextExtractionException $exception) {
            Storage::disk('local')->delete($storedPath);
            throw $exception;
        }

        try {
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

            return $version;
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($storedPath);
            throw $exception;
        }
    }

    public function createRevision(Document $document, UploadedFile $file, ?string $revisionNote = null): DocumentVersion
    {
        return DB::transaction(function () use ($document, $file, $revisionNote) {
            $lockedDocument = Document::query()
                ->whereKey($document->id)
                ->lockForUpdate()
                ->firstOrFail();

            $nextVersionNumber = ((int) $lockedDocument->versions()->max('version_number')) + 1;

            $storedPath = $file->store(
                "document-revisions/user_{$lockedDocument->user_id}/document_{$lockedDocument->id}",
                'local',
            );

            if ($storedPath === false) {
                throw new RuntimeException('Dokumen revisi gagal disimpan.');
            }

            try {
                $extractedText = $this->textExtractionService->extract(
                    Storage::disk('local')->path($storedPath),
                    $file->getClientOriginalExtension(),
                );
            } catch (TextExtractionException $exception) {
                Storage::disk('local')->delete($storedPath);
                throw $exception;
            }

            try {
                $version = $lockedDocument->versions()->create([
                    'version_number' => $nextVersionNumber,
                    'file_path' => $storedPath,
                    'file_original_name' => $file->getClientOriginalName(),
                    'file_type' => strtolower($file->getClientOriginalExtension()),
                    'file_size' => $file->getSize(),
                    'extracted_text' => $extractedText,
                    'revision_note' => $revisionNote,
                    'uploaded_at' => now(),
                ]);

                $lockedDocument->update([
                    'latest_version_id' => $version->id,
                    'latest_score' => null,
                    'status' => Document::STATUS_REVISED,
                ]);

                return $version;
            } catch (\Throwable $exception) {
                Storage::disk('local')->delete($storedPath);
                throw $exception;
            }
        });
    }
}
