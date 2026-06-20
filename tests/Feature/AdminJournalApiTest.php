<?php

namespace Tests\Feature;

use App\Models\Journal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminJournalApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_journal_with_ai_ready_metadata(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        Sanctum::actingAs($admin);

        $this->postJson('/api/admin/journals', $this->validJournalPayload())
            ->assertCreated()
            ->assertJsonPath('message', 'Data jurnal berhasil ditambahkan.')
            ->assertJsonPath('data.name', 'AI Engineering Journal')
            ->assertJsonPath('data.is_active', true)
            ->assertJsonPath('data.verification_status', 'verified')
            ->assertJsonPath('data.eligibility_score', 100);

        $this->assertDatabaseHas('journals', [
            'name' => 'AI Engineering Journal',
            'sinta_level' => 'S1',
            'is_active' => true,
            'verification_status' => 'verified',
            'eligibility_score' => 100,
        ]);
    }

    public function test_admin_journal_validation_rejects_invalid_enum_and_url_values(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        Sanctum::actingAs($admin);

        $this->postJson('/api/admin/journals', [
            'name' => 'Invalid Journal',
            'sinta_level' => 'S7',
            'website_url' => 'javascript:alert(1)',
            'verification_status' => 'published',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['sinta_level', 'website_url', 'verification_status']);
    }

    public function test_admin_can_list_filter_update_and_read_journal_stats(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $journal = Journal::create($this->validJournalPayload([
            'name' => 'Filtered AI Journal',
            'sinta_level' => 'S2',
            'subject_area' => 'Computer Science',
        ]));
        Journal::create($this->validJournalPayload([
            'name' => 'Inactive Humanities Journal',
            'sinta_level' => 'S4',
            'subject_area' => 'Humanities',
            'is_active' => false,
            'verification_status' => 'pending_review',
        ]));
        Sanctum::actingAs($admin);

        $this->getJson('/api/admin/journals?search=Filtered&sinta_level=S2&is_active=1&verification_status=verified')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.id', $journal->id);

        $this->putJson("/api/admin/journals/{$journal->id}", [
            'is_active' => false,
            'verification_status' => 'pending_review',
            'website_url' => 'https://example.com/filtered-ai',
        ])
            ->assertOk()
            ->assertJsonPath('message', 'Data jurnal berhasil diperbarui.')
            ->assertJsonPath('data.is_active', false)
            ->assertJsonPath('data.verification_status', 'pending_review');

        $this->getJson('/api/admin/journals/stats')
            ->assertOk()
            ->assertJsonPath('data.total', 2)
            ->assertJsonPath('data.pending_review', 2)
            ->assertJsonPath('data.verified', 0)
            ->assertJsonPath('data.minimum_ai_score', 70);
    }

    public function test_admin_can_import_journals_from_csv_as_pending_inactive_records(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        Sanctum::actingAs($admin);
        $csv = implode("\n", [
            'name,publisher,sinta_level,subject_area,focus_scope,keywords,website_url,template_url,author_guideline_url',
            'Imported Journal,Publisher,S3,Computer Science,AI research scope,ai;ml,https://example.com,https://example.com/template,https://example.com/guide',
        ]);

        $this->post('/api/admin/journals/import', [
            'file' => UploadedFile::fake()->createWithContent('journals.csv', $csv),
        ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('message', 'Import CSV jurnal selesai.')
            ->assertJsonPath('data.summary.imported', 1)
            ->assertJsonPath('data.summary.updated', 0)
            ->assertJsonPath('data.summary.failed', 0);

        $this->assertDatabaseHas('journals', [
            'name' => 'Imported Journal',
            'is_active' => false,
            'verification_status' => 'pending_review',
            'eligibility_score' => 100,
        ]);
    }

    public function test_regular_user_cannot_manage_admin_journals(): void
    {
        $user = User::factory()->create();
        $journal = Journal::create($this->validJournalPayload());
        Sanctum::actingAs($user);

        $this->getJson('/api/admin/journals')->assertForbidden();
        $this->postJson('/api/admin/journals', $this->validJournalPayload())->assertForbidden();
        $this->putJson("/api/admin/journals/{$journal->id}", ['is_active' => false])->assertForbidden();
        $this->deleteJson("/api/admin/journals/{$journal->id}")->assertForbidden();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validJournalPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'AI Engineering Journal',
            'publisher' => 'Academic Publisher',
            'sinta_level' => 'S1',
            'subject_area' => 'Computer Science',
            'focus_scope' => 'Artificial intelligence and software engineering research.',
            'keywords' => 'artificial intelligence; software engineering',
            'website_url' => 'https://example.com/journal',
            'template_url' => 'https://example.com/template',
            'author_guideline_url' => 'https://example.com/guideline',
            'is_active' => true,
            'verification_status' => 'verified',
        ], $overrides);
    }
}
