<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\AiAnalysisException;
use App\Exceptions\AiProviderException;
use App\Http\Controllers\Api\Traits\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\AnalysisResult;
use App\Models\Document;
use App\Models\Rubric;
use App\Services\AiAnalysisService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class AnalysisController extends Controller
{
    use ApiResponse;

    public function analyze(
        Request $request,
        Document $document,
        AiAnalysisService $aiAnalysisService,
    ): JsonResponse {
        if (! $this->canAccess($request, $document)) {
            return $this->forbidden('Tidak memiliki akses.');
        }

        $latestVersion = $document->latestVersion;

        if (! $latestVersion || blank($latestVersion->extracted_text)) {
            return $this->validationError(null, 'Teks dokumen belum tersedia untuk dianalisis.');
        }

        $rubrics = Rubric::query()
            ->where('document_type_id', $document->document_type_id)
            ->where('is_active', true)
            ->orderBy('id')
            ->get();

        if ($rubrics->isEmpty()) {
            return $this->validationError(null, 'Rubrik aktif untuk jenis dokumen ini belum tersedia.');
        }

        try {
            $aiResult = $aiAnalysisService->analyze($document);

            $analysis = DB::transaction(function () use ($document, $latestVersion, $aiResult): AnalysisResult {
                $analysis = AnalysisResult::create([
                    'document_id' => $document->id,
                    'document_version_id' => $latestVersion->id,
                    'total_score' => $aiResult['total_score'],
                    'status' => $aiResult['status'],
                    'summary' => $aiResult['summary'],
                    'main_issues' => $aiResult['main_issues'],
                    'recommendations' => $aiResult['recommendations'],
                    'revision_priorities' => $aiResult['revision_priorities'],
                    'raw_ai_response' => $aiResult,
                ]);

                foreach ($aiResult['aspect_scores'] as $aspect) {
                    $analysis->aspectScores()->create($aspect);
                }

                $document->update([
                    'status' => $this->mapDocumentStatus($analysis->total_score),
                    'latest_score' => $analysis->total_score,
                ]);

                return $analysis;
            });
        } catch (AiAnalysisException|AiProviderException $exception) {
            report($exception);

            return $this->badGateway('Analisis AI gagal. Silakan coba kembali.');
        } catch (Throwable $exception) {
            report($exception);

            return $this->serverError('Analisis AI gagal karena kesalahan internal.');
        }

        return $this->created(
            $analysis->load(['documentVersion', 'aspectScores']),
            'Analisis AI berhasil.',
        );
    }

    public function latest(Request $request, Document $document): JsonResponse
    {
        if (! $this->canAccess($request, $document)) {
            return $this->forbidden('Tidak memiliki akses.');
        }

        $analysis = $document->analysisResults()
            ->with(['documentVersion', 'aspectScores'])
            ->latest()
            ->first();

        if (! $analysis) {
            return $this->notFound('Hasil analisis belum tersedia.');
        }

        return $this->success($analysis);
    }

    private function canAccess(Request $request, Document $document): bool
    {
        return $document->user_id === $request->user()->id || $request->user()->isAdmin();
    }

    private function mapDocumentStatus(int $score): string
    {
        return match (true) {
            $score >= 85 => Document::STATUS_READY,
            $score >= 70 => Document::STATUS_ANALYZED,
            default => Document::STATUS_NEED_REVISION,
        };
    }
}
