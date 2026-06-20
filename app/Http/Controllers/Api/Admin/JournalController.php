<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\Traits\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Journal;
use App\Services\JournalEligibilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class JournalController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'sinta_level' => ['nullable', Rule::in($this->sintaLevels())],
            'verification_status' => ['nullable', Rule::in($this->verificationStatuses())],
            'is_active' => ['nullable', 'in:1,0,true,false'],
        ]);

        $query = Journal::query();

        if (! empty($validated['search'])) {
            $query->where(function ($q) use ($validated) {
                $q->where('name', 'like', '%' . $validated['search'] . '%')
                    ->orWhere('publisher', 'like', '%' . $validated['search'] . '%')
                    ->orWhere('subject_area', 'like', '%' . $validated['search'] . '%')
                    ->orWhere('keywords', 'like', '%' . $validated['search'] . '%')
                    ->orWhere('focus_scope', 'like', '%' . $validated['search'] . '%');
            });
        }

        if (! empty($validated['sinta_level'])) {
            $query->where('sinta_level', $validated['sinta_level']);
        }

        if (! empty($validated['verification_status'])) {
            $query->where('verification_status', $validated['verification_status']);
        }

        if (array_key_exists('is_active', $validated)) {
            $query->where('is_active', filter_var($validated['is_active'], FILTER_VALIDATE_BOOLEAN));
        }

        $journals = $query
            ->orderByRaw("FIELD(sinta_level, 'S1','S2','S3','S4','S5','S6')")
            ->orderBy('name')
            ->paginate(20);

        return $this->paginated($journals);
    }

    public function stats(): JsonResponse
    {
        $bySinta = Journal::query()
            ->selectRaw('COALESCE(sinta_level, ?) as sinta_level, COUNT(*) as total', ['Belum diisi'])
            ->groupBy('sinta_level')
            ->orderByRaw("CASE sinta_level WHEN 'S1' THEN 1 WHEN 'S2' THEN 2 WHEN 'S3' THEN 3 WHEN 'S4' THEN 4 WHEN 'S5' THEN 5 WHEN 'S6' THEN 6 ELSE 7 END")
            ->get()
            ->map(fn ($item) => [
                'sinta_level' => $item->sinta_level,
                'total' => (int) $item->total,
            ]);

        return $this->success([
            'total' => Journal::count(),
            'active' => Journal::where('is_active', true)->count(),
            'pending_review' => Journal::where('verification_status', 'pending_review')->count(),
            'verified' => Journal::where('verification_status', 'verified')->count(),
            'ai_ready' => Journal::query()
                ->where('is_active', true)
                ->where('verification_status', 'verified')
                ->where('eligibility_score', '>=', JournalEligibilityService::MINIMUM_AI_SCORE)
                ->count(),
            'minimum_ai_score' => JournalEligibilityService::MINIMUM_AI_SCORE,
            'by_sinta' => $bySinta,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate($this->journalRules(isUpdate: false));

        $journal = Journal::create(array_merge([
            'is_active' => false,
            'verification_status' => 'pending_review',
        ], $validated));

        return $this->created($journal, 'Data jurnal berhasil ditambahkan.');
    }

    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt'],
        ]);

        $file = fopen($request->file('file')->getRealPath(), 'r');

        $header = fgetcsv($file);

        if (! $header) {
            fclose($file);

            return $this->validationError(null, 'CSV kosong atau format tidak valid.');
        }

        $header = array_map(function ($item) {
            $item = preg_replace('/^\xEF\xBB\xBF/', '', (string) $item);

            return strtolower(trim($item));
        }, $header);

        if (! in_array('name', $header)) {
            fclose($file);

            return $this->validationError(null, 'Kolom wajib name tidak ditemukan.');
        }

        $imported = 0;
        $updated = 0;
        $failed = 0;
        $errors = [];

        while (($row = fgetcsv($file)) !== false) {
            if (count($row) !== count($header)) {
                $failed++;
                continue;
            }

            $data = array_combine($header, $row);

            if (! $data || empty(trim($data['name'] ?? ''))) {
                $failed++;
                continue;
            }

            $validator = Validator::make($data, [
                'name' => ['required', 'string'],
                'sinta_level' => ['nullable', Rule::in($this->sintaLevels())],
                'subject_area' => ['nullable', 'string'],
                'website_url' => $this->nullableHttpUrlRules(),
                'editor_url' => $this->nullableHttpUrlRules(),
                'template_url' => $this->nullableHttpUrlRules(),
                'author_guideline_url' => $this->nullableHttpUrlRules(),
                'source_url' => $this->nullableHttpUrlRules(),
            ]);

            if ($validator->fails()) {
                $failed++;

                $errors[] = [
                    'name' => $data['name'] ?? '-',
                    'errors' => $validator->errors()->all(),
                ];

                continue;
            }

            $journalData = [
                'name' => $data['name'] ?? null,
                'publisher' => $data['publisher'] ?? null,
                'sinta_level' => $data['sinta_level'] ?? null,
                'subject_area' => $data['subject_area'] ?? null,
                'focus_scope' => $data['focus_scope'] ?? null,
                'keywords' => $data['keywords'] ?? null,
                'p_issn' => $this->normalizeIssn($data['p_issn'] ?? null),
                'e_issn' => $this->normalizeIssn($data['e_issn'] ?? null),
                'website_url' => $data['website_url'] ?? null,
                'editor_url' => $data['editor_url'] ?? null,
                'template_url' => $data['template_url'] ?? null,
                'author_guideline_url' => $data['author_guideline_url'] ?? null,
                'indexing' => $data['indexing'] ?? null,
                'impact' => $data['impact'] ?? null,
                'h5_index' => $data['h5_index'] ?? null,
                'citations_5yr' => $data['citations_5yr'] ?? null,
                'citations_total' => $data['citations_total'] ?? null,
                'source_url' => $data['source_url'] ?? null,
                'raw_text' => $data['raw_text'] ?? null,
                'last_verified_at' => ! empty($data['last_verified_at']) ? $data['last_verified_at'] : null,
                'is_active' => false,
                'verification_status' => 'pending_review',
            ];

            if (empty($journalData['publisher']) && ! empty($journalData['raw_text'])) {
                $journalData['publisher'] = $this->extractPublisherFromRawText(
                    $journalData['name'],
                    $journalData['raw_text']
                );
            }

            $existing = $this->findExistingJournal($journalData);

            if ($existing) {
                $existing->update($journalData);
                $updated++;
            } else {
                Journal::create($journalData);
                $imported++;
            }
        }

        fclose($file);

        return $this->success([
            'summary' => [
                'imported' => $imported,
                'updated' => $updated,
                'failed' => $failed,
            ],
            'errors' => $errors,
        ], 'Import CSV jurnal selesai.');
    }

    public function update(Request $request, Journal $journal): JsonResponse
    {
        $validated = $request->validate($this->journalRules(isUpdate: true));

        $journal->update($validated);

        return $this->success($journal, 'Data jurnal berhasil diperbarui.');
    }

    public function destroy(Journal $journal): JsonResponse
    {
        $journal->delete();

        return $this->success(null, 'Data jurnal berhasil dihapus.');
    }

    private function normalizeIssn(?string $issn): ?string
    {
        $issn = trim((string) $issn);

        if ($issn === '' || $issn === '0') {
            return null;
        }

        return $issn;
    }

    private function findExistingJournal(array $journalData): ?Journal
    {
        if (! empty($journalData['e_issn'])) {
            $journal = Journal::where('e_issn', $journalData['e_issn'])->first();

            if ($journal) {
                return $journal;
            }
        }

        if (! empty($journalData['p_issn'])) {
            $journal = Journal::where('p_issn', $journalData['p_issn'])->first();

            if ($journal) {
                return $journal;
            }
        }

        return Journal::where('name', $journalData['name'])->first();
    }

    private function extractPublisherFromRawText(string $name, string $rawText): ?string
    {
        $text = trim($rawText);

        $text = str_replace($name, '', $text);
        $text = str_replace('Google Scholar Website Editor URL', '', $text);
        $text = str_replace('Website Editor URL', '', $text);
        $text = str_replace('Google Scholar', '', $text);

        $parts = preg_split('/P-ISSN\s*:/i', $text);

        if (! $parts || empty($parts[0])) {
            return null;
        }

        $publisher = trim($parts[0]);

        return $publisher !== '' ? substr($publisher, 0, 255) : null;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function journalRules(bool $isUpdate): array
    {
        $sometimes = $isUpdate ? ['sometimes'] : [];

        return [
            'name' => [...$sometimes, 'required', 'string', 'max:255'],
            'publisher' => [...$sometimes, 'nullable', 'string', 'max:255'],
            'sinta_level' => [...$sometimes, 'nullable', Rule::in($this->sintaLevels())],
            'subject_area' => [...$sometimes, 'nullable', 'string', 'max:255'],
            'focus_scope' => [...$sometimes, 'nullable', 'string'],
            'keywords' => [...$sometimes, 'nullable', 'string'],
            'p_issn' => [...$sometimes, 'nullable', 'string', 'max:50'],
            'e_issn' => [...$sometimes, 'nullable', 'string', 'max:50'],
            'website_url' => [...$sometimes, ...$this->nullableHttpUrlRules()],
            'editor_url' => [...$sometimes, ...$this->nullableHttpUrlRules()],
            'template_url' => [...$sometimes, ...$this->nullableHttpUrlRules()],
            'author_guideline_url' => [...$sometimes, ...$this->nullableHttpUrlRules()],
            'indexing' => [...$sometimes, 'nullable', 'string'],
            'impact' => [...$sometimes, 'nullable', 'string', 'max:255'],
            'h5_index' => [...$sometimes, 'nullable', 'string', 'max:255'],
            'citations_5yr' => [...$sometimes, 'nullable', 'string', 'max:255'],
            'citations_total' => [...$sometimes, 'nullable', 'string', 'max:255'],
            'source_url' => [...$sometimes, ...$this->nullableHttpUrlRules()],
            'raw_text' => [...$sometimes, 'nullable', 'string'],
            'last_verified_at' => [...$sometimes, 'nullable', 'date'],
            'is_active' => [...$sometimes, 'boolean'],
            'verification_status' => [
                ...$sometimes,
                'nullable',
                Rule::in($this->verificationStatuses()),
            ],
        ];
    }

    /**
     * @return array<int, string>
     */
    private function nullableHttpUrlRules(): array
    {
        return ['nullable', 'url', 'starts_with:http://,https://'];
    }

    /**
     * @return array<int, string>
     */
    private function sintaLevels(): array
    {
        return ['S1', 'S2', 'S3', 'S4', 'S5', 'S6'];
    }

    /**
     * @return array<int, string>
     */
    private function verificationStatuses(): array
    {
        return ['pending_review', 'verified', 'rejected'];
    }
}
