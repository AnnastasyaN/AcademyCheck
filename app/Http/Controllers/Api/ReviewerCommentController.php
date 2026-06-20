<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\AiProviderException;
use App\Exceptions\ReviewerCommentParserException;
use App\Http\Controllers\Api\Traits\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\ReviewerComment;
use App\Services\ReviewerCommentParserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Throwable;

class ReviewerCommentController extends Controller
{
    use ApiResponse;

    public function index(Request $request, Document $document): JsonResponse
    {
        if (! $this->canAccess($request, $document)) {
            return $this->accessDenied();
        }

        if (! $this->isArticle($document)) {
            return $this->articleOnly();
        }

        return $this->success(
            $document->reviewerComments()
                ->with(['response.revisedVersion'])
                ->orderBy('reviewer_label')
                ->orderBy('comment_number')
                ->orderBy('id')
                ->get(),
        );
    }

    public function store(Request $request, Document $document): JsonResponse
    {
        if (! $this->canAccess($request, $document)) {
            return $this->accessDenied();
        }

        if (! $this->isArticle($document)) {
            return $this->articleOnly();
        }

        $validated = $request->validate([
            'reviewer_label' => ['required', 'string', 'max:100'],
            'comment_number' => ['nullable', 'integer', 'min:1'],
            'original_comment' => ['required', 'string'],
            'related_section' => ['nullable', 'string', 'max:100'],
            'priority' => [
                'required',
                Rule::in([
                    ReviewerComment::PRIORITY_MINOR,
                    ReviewerComment::PRIORITY_MAJOR,
                    ReviewerComment::PRIORITY_CRITICAL,
                ]),
            ],
            'status' => ['nullable', Rule::in($this->statuses())],
        ]);

        $comment = $document->reviewerComments()->create([
            ...$validated,
            'status' => $validated['status'] ?? ReviewerComment::STATUS_PENDING,
        ]);

        return $this->created($comment, 'Komentar reviewer berhasil ditambahkan.');
    }

    public function parseWithAi(
        Request $request,
        Document $document,
        ReviewerCommentParserService $parserService,
    ): JsonResponse {
        if (! $this->canAccess($request, $document)) {
            return $this->accessDenied();
        }

        if (! $this->isArticle($document)) {
            return $this->articleOnly();
        }

        $validated = $request->validate([
            'reviewer_text' => ['required', 'string', 'max:30000'],
            'save_to_database' => ['nullable', 'boolean'],
        ]);

        try {
            $result = $parserService->parse($document, $validated['reviewer_text']);
            $savedComments = [];

            if ($validated['save_to_database'] ?? false) {
                $savedComments = DB::transaction(
                    fn () => $document->reviewerComments()->createMany($result['comments'])->all(),
                );
            }
        } catch (ReviewerCommentParserException|AiProviderException $exception) {
            report($exception);

            return $this->badGateway('Gagal memproses komentar reviewer dengan AI. Silakan coba kembali.');
        } catch (Throwable $exception) {
            report($exception);

            return $this->serverError('Gagal memproses komentar reviewer karena kesalahan internal.');
        }

        return $this->success([
            'parsed_comments' => $result['comments'],
            'saved_comments' => $savedComments,
        ], 'Komentar reviewer berhasil diproses AI.');
    }

    public function update(Request $request, ReviewerComment $reviewerComment): JsonResponse
    {
        $document = $reviewerComment->document;

        if (! $this->canAccess($request, $document)) {
            return $this->accessDenied();
        }

        if (! $this->isArticle($document)) {
            return $this->articleOnly();
        }

        $validated = $request->validate([
            'reviewer_label' => ['sometimes', 'required', 'string', 'max:100'],
            'comment_number' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'original_comment' => ['sometimes', 'required', 'string'],
            'related_section' => ['sometimes', 'nullable', 'string', 'max:100'],
            'priority' => [
                'sometimes',
                Rule::in([
                    ReviewerComment::PRIORITY_MINOR,
                    ReviewerComment::PRIORITY_MAJOR,
                    ReviewerComment::PRIORITY_CRITICAL,
                ]),
            ],
        ]);

        $reviewerComment->update($validated);

        return $this->success($reviewerComment->fresh('response'), 'Komentar reviewer berhasil diperbarui.');
    }

    public function updateStatus(Request $request, ReviewerComment $reviewerComment): JsonResponse
    {
        $document = $reviewerComment->document;

        if (! $this->canAccess($request, $document)) {
            return $this->accessDenied();
        }

        if (! $this->isArticle($document)) {
            return $this->articleOnly();
        }

        $validated = $request->validate([
            'status' => ['required', Rule::in($this->statuses())],
        ]);

        $reviewerComment->update($validated);

        return $this->success($reviewerComment, 'Status komentar reviewer berhasil diperbarui.');
    }

    public function destroy(Request $request, ReviewerComment $reviewerComment): JsonResponse
    {
        $document = $reviewerComment->document;

        if (! $this->canAccess($request, $document)) {
            return $this->accessDenied();
        }

        if (! $this->isArticle($document)) {
            return $this->articleOnly();
        }

        $reviewerComment->delete();

        return $this->success(null, 'Komentar reviewer berhasil dihapus.');
    }

    private function statuses(): array
    {
        return [
            ReviewerComment::STATUS_PENDING,
            ReviewerComment::STATUS_IN_PROGRESS,
            ReviewerComment::STATUS_DONE,
            ReviewerComment::STATUS_REJECTED_WITH_REASON,
        ];
    }

    private function canAccess(Request $request, Document $document): bool
    {
        return $document->user_id === $request->user()->id || $request->user()->isAdmin();
    }

    private function isArticle(Document $document): bool
    {
        return $document->documentType()->where('name', 'article')->exists();
    }

    private function accessDenied(): JsonResponse
    {
        return $this->forbidden('Anda tidak memiliki akses ke dokumen ini.');
    }

    private function articleOnly(): JsonResponse
    {
        return $this->validationError(null, 'Reviewer Revision Mapping hanya tersedia untuk artikel ilmiah.');
    }
}
