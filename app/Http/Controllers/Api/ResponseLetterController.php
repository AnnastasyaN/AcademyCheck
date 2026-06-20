<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Traits\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Document;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ResponseLetterController extends Controller
{
    use ApiResponse;

    public function download(Request $request, Document $document): JsonResponse|StreamedResponse
    {
        if (! $this->canAccess($request, $document)) {
            return $this->forbidden('Anda tidak memiliki akses ke dokumen ini.');
        }

        if (! $document->documentType()->where('name', 'article')->exists()) {
            return $this->validationError(null, 'Response to Reviewers hanya tersedia untuk artikel ilmiah.');
        }

        $comments = $document->reviewerComments()
            ->with(['response.revisedVersion'])
            ->orderBy('reviewer_label')
            ->orderBy('comment_number')
            ->orderBy('id')
            ->get();

        if ($comments->isEmpty()) {
            return $this->validationError(null, 'Belum ada komentar reviewer untuk artikel ini.');
        }

        $fileName = "response_to_reviewers_document_{$document->id}.pdf";
        $filePath = "response-letters/user_{$document->user_id}/document_{$document->id}/{$fileName}";
        $pdfContent = Pdf::loadView('reports.response-letter', [
            'document' => $document,
            'comments' => $comments,
            'generatedAt' => now(),
        ])->setPaper('a4', 'landscape')->output();

        if (! Storage::disk('local')->put($filePath, $pdfContent)) {
            return $this->serverError('Response to Reviewers gagal dibuat.');
        }

        return Storage::disk('local')->download($filePath, $fileName, [
            'Content-Type' => 'application/pdf',
        ]);
    }

    private function canAccess(Request $request, Document $document): bool
    {
        return $document->user_id === $request->user()->id || $request->user()->isAdmin();
    }
}
