Saya sudah melakukan audit tanpa perubahan kode. Verifikasi runtime:

`php artisan test` pass: 93 tests, 480 assertions.  
`npm run build` pass.  
`git status` bersih setelah audit.

**Project Readiness Score: 78%**

Core API cukup solid dan banyak alur penting sudah dites. Yang menahan readiness: proteksi halaman web masih client-side, fitur update/delete dokumen belum tersedia, admin journal belum punya coverage test memadai, validasi journal metadata masih longgar, dan beberapa constraint database belum mengunci integritas data.

**A. Feature Testing Report**

Authentication & Authorization: API auth berjalan baik via Sanctum di [routes/api.php](<D:/laragon/www/AcadCheck-UAS/routes/api.php:24>). Register/login/logout/me sudah ada dan dites. Inactive user ditolak saat login. Admin middleware tersedia di [EnsureUserIsAdmin.php](<D:/laragon/www/AcadCheck-UAS/app/Http/Middleware/EnsureUserIsAdmin.php:11>). Temuan utama: halaman Blade seperti dashboard, documents, dan admin bisa dirender tanpa middleware server-side di [routes/web.php](<D:/laragon/www/AcadCheck-UAS/routes/web.php:12>). Data tetap dilindungi API, tetapi route protection web belum memenuhi standar.

Admin Features: Dashboard admin, user list/status, document list, rubric update berjalan dan dites. Journal management tersedia untuk list, stats, import CSV, update, activate/deactivate, delete di [JournalController.php](<D:/laragon/www/AcadCheck-UAS/app/Http/Controllers/Api/Admin/JournalController.php:13>), tetapi belum ada endpoint create manual, validasi enum/URL masih string bebas, dan automated test khusus admin journal belum ditemukan.

User Features: Dashboard, upload document, view document, upload revision, history, AI review, comparison, reviewer mapping, response matrix/letter berjalan dan tercakup test. Gap besar: tidak ada endpoint update document dan delete document; route hanya menyediakan GET/POST dokumen di [routes/api.php](<D:/laragon/www/AcadCheck-UAS/routes/api.php:45>).

AI Recommendation Module: Journal recommendation sudah membatasi jurnal aktif, verified, eligibility >= 70, lalu memfilter output AI agar hanya journal ID dari daftar kandidat yang tersimpan di [JournalRecommendationController.php](<D:/laragon/www/AcadCheck-UAS/app/Http/Controllers/Api/JournalRecommendationController.php:62>). Ranking by `fit_score` sudah ada. Prompt juga sudah memasukkan mitigasi prompt injection. Risiko: logic masih besar di controller, belum service khusus `JournalRecommendationService` seperti nama yang diminta.

End-to-End: Alur register -> login -> upload artikel -> AI review -> journal recommendation -> open journal website -> logout didukung. AI live tidak dites karena test memakai mock provider; ini tepat untuk CI, tetapi sebelum production perlu smoke test dengan kredensial AI nyata.

**B. API Testing Report**

| Method | Endpoint | Function | Status | Issue |
| --- | --- | --- | --- | --- |
| POST | `/api/register` | Register | OK | No password confirmation; no throttle eksplisit |
| POST | `/api/login` | Login | OK | No throttle eksplisit |
| POST | `/api/logout` | Logout | OK | Token saat ini dihapus |
| GET | `/api/me` | Current user | OK | - |
| GET | `/api/user/dashboard` | User dashboard | OK | - |
| GET | `/api/test-ai` | AI smoke test local | OK | Local only; good |
| GET | `/api/document-types` | Active document types | OK | - |
| GET | `/api/rubrics` | List rubrics | OK | Auth required |
| GET | `/api/documents` | List own docs | OK | No pagination |
| POST | `/api/documents` | Upload doc | OK | PDF/DOCX 10MB only |
| GET | `/api/documents/{document}` | Detail | OK | Owner/admin protected |
| PUT | `/api/documents/{document}` | Update doc | Missing | Required feature absent |
| DELETE | `/api/documents/{document}` | Delete doc | Missing | Required feature absent |
| POST | `/api/documents/{document}/analyze` | AI analysis | OK | Live AI dependency |
| GET | `/api/documents/{document}/analysis` | Latest analysis | OK | - |
| GET | `/api/documents/{document}/journal-recommendations` | List recommendations | OK | - |
| POST | `/api/documents/{document}/journal-recommendations` | Generate recommendations | OK | Controller too large |
| GET | `/api/documents/{document}/versions` | Version history | OK | - |
| POST | `/api/documents/{document}/versions` | Upload revision | OK | Owner only |
| GET | `/api/documents/{document}/comparison` | Compare versions | OK | Docs omit required params |
| GET | `/api/articles/{document}/reviewer-comments` | List comments | OK | Article only |
| POST | `/api/articles/{document}/reviewer-comments` | Add comment | OK | - |
| POST | `/api/articles/{document}/reviewer-comments/parse` | AI parse comments | OK | Live AI dependency |
| PUT | `/api/reviewer-comments/{id}` | Update comment | OK | - |
| PUT | `/api/reviewer-comments/{id}/status` | Update status | OK | - |
| DELETE | `/api/reviewer-comments/{id}` | Delete comment | OK | Cascades response |
| POST | `/api/reviewer-comments/{id}/responses` | Save author response | OK | Version ownership checked |
| POST | `/api/reviewer-comments/{id}/generate-response` | AI response draft | OK | Live AI dependency |
| GET | `/api/articles/{document}/response-matrix` | Response matrix | OK | - |
| GET | `/api/articles/{document}/response-letter` | PDF export | OK | Private storage |
| GET | `/api/admin/dashboard` | Admin dashboard | OK | Admin middleware |
| GET | `/api/admin/users` | User management | OK | - |
| PUT | `/api/admin/users/{user}/status` | Activate/deactivate user | OK | Revokes tokens on deactivate |
| GET | `/api/admin/documents` | Admin document list | OK | - |
| GET | `/api/admin/journals/stats` | Journal stats | Partial | No dedicated test found |
| GET | `/api/admin/journals` | Journal list/filter | Partial | Weak filter validation |
| POST | `/api/admin/journals/import` | CSV import | Partial | No max file size; no dedicated test |
| PUT | `/api/admin/journals/{journal}` | Update/verify/activate | Partial | Enum/URL validation weak |
| DELETE | `/api/admin/journals/{journal}` | Delete journal | Partial | Permanent delete; no dedicated test |
| PUT | `/api/admin/rubrics/{rubric}` | Update rubric | OK | - |

**C. Security Review**

High: Web route protection is missing server-side. JS checks `localStorage`, but `/admin/dashboard` and other pages still render from server without auth middleware in [routes/web.php](<D:/laragon/www/AcadCheck-UAS/routes/web.php:27>).

Medium: Tokens are stored in `localStorage` in [app.js](<D:/laragon/www/AcadCheck-UAS/resources/js/app.js:2>), increasing impact of XSS. Consider httpOnly cookie Sanctum SPA mode or tighter CSP.

Medium: No explicit rate limiting on login/register. Add throttle middleware.

Medium: `User` fillable includes `role` and `is_active` in [User.php](<D:/laragon/www/AcadCheck-UAS/app/Models/User.php:15>). Current controllers are safe, but future `User::create($request->all())` would be dangerous.

Good: Uploaded files are stored on private local disk; Laravel signed storage routes protect access. External journal links use `noopener noreferrer`.

**D. Database Review**

Good: Core FK cascade/restrict is mostly correct for users, documents, versions, analysis, reviewer mapping, and recommendations.

Issues:
`documents.latest_version_id` is indexed but not a foreign key in [documents migration](<D:/laragon/www/AcadCheck-UAS/database/migrations/2026_06_14_022824_create_documents_table.php:34>). This can leave stale pointers.

`journals` has no unique constraint for `name`, `p_issn`, or `e_issn`; duplicate prevention exists only in application logic in [JournalController.php](<D:/laragon/www/AcadCheck-UAS/app/Http/Controllers/Api/Admin/JournalController.php:279>).

`journal_recommendations` lacks unique `(document_id, journal_id)` constraint in [journal recommendations migration](<D:/laragon/www/AcadCheck-UAS/database/migrations/2026_06_17_162023_create_journal_recommendations_table.php:11>).

Journal status fields are plain strings, not constrained enum/check columns.

**E. Bug List Prioritized**

Critical: none confirmed by current test suite.

High:
1. Web pages lack server-side auth/admin middleware. Impact: protected pages render without session. Fix: wrap web routes with auth/session or convert fully to SPA guarded entry.
2. Update/delete document features are missing. Impact: required user flow incomplete. Fix: add `PUT/PATCH` and `DELETE /api/documents/{document}` with owner/admin rules.
3. Admin journal module lacks dedicated automated tests. Impact: import/verification/activation regressions may slip. Fix: add feature tests for CSV import, filters, validation, status update, delete.

Medium:
1. Admin journal validation too permissive for `sinta_level`, `verification_status`, and URLs.
2. Duplicate journal risk due to missing DB unique constraints.
3. Existing tokens remain valid if `is_active` is changed outside admin controller.
4. REST docs incomplete for admin journals, journal recommendation, and comparison query params.

Low:
1. Admin journal controller repeats admin role checks even though route already uses `admin` middleware.
2. `GET /api/documents` is unpaginated.
3. `JournalRecommendationController` is long and should be moved into a service.

**F. Recommended Fix Order**

1. Add server-side protection for all web dashboard/document/admin routes.
2. Add document update and delete endpoints with tests.
3. Harden admin journal validation and add AdminJournalApiTest.
4. Add DB constraints for journals, recommendations, and `latest_version_id`.
5. Add auth throttling and active-user middleware for every authenticated request.
6. Extract `JournalRecommendationService`.
7. Update `docs/rest-api.md` to match current endpoints.
