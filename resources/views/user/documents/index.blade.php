@extends('layouts.app', ['title' => 'Document Library | AcadCheck AI'])

@section('page', 'document-library')

@section('content')
<main class="workspace-shell">
    <header class="workspace-header">
        <a href="/dashboard" class="brand-mark workspace-brand" aria-label="AcadCheck AI">
            <span class="brand-icon">A</span>
            <span>AcadCheck <strong>AI</strong></span>
        </a>

        <nav class="workspace-nav" aria-label="Navigasi utama">
            <a href="/dashboard">Dashboard</a>
            <a href="/documents" aria-current="page">Document library</a>
            <button type="button" data-logout>Keluar</button>
        </nav>
    </header>

    <section class="library-shell">
        <div class="library-heading">
            <div>
                <p class="eyebrow">Your archive</p>
                <h1>Document Library</h1>
                <p>Kelola semua dokumen akademik dan lanjutkan proses analisis dari satu tempat.</p>
            </div>

            <a href="/documents/upload" class="primary-button button-link">Upload dokumen</a>
        </div>

        <section class="library-filters" aria-label="Filter dokumen">
            <label class="search-field">
                <span>Cari dokumen</span>
                <input type="search" id="documentSearch" placeholder="Cari judul atau topik...">
            </label>

            <label>
                <span>Jenis</span>
                <select id="documentTypeFilter">
                    <option value="">Semua jenis</option>
                </select>
            </label>

            <label>
                <span>Status</span>
                <select id="documentStatusFilter">
                    <option value="">Semua status</option>
                    <option value="uploaded">Uploaded</option>
                    <option value="analyzed">Analyzed</option>
                    <option value="need_revision">Need revision</option>
                    <option value="revised">Revised</option>
                    <option value="ready">Ready</option>
                </select>
            </label>

            <button type="button" id="clearDocumentFilters" class="filter-reset">Reset filter</button>
        </section>

        <div id="libraryAlert" class="form-alert hidden" role="alert"></div>

        <section id="documentLoading" class="library-state">
            <span class="loading-dot"></span>
            <div>
                <strong>Memuat dokumen</strong>
                <p>Menyiapkan arsip akademik Anda...</p>
            </div>
        </section>

        <section id="documentLibraryContent" class="library-content hidden">
            <div class="library-meta">
                <p><strong id="visibleDocumentCount">0</strong> dokumen ditampilkan</p>
                <span id="totalDocumentCount">0 total dokumen</span>
            </div>

            <div class="document-table-wrap">
                <table class="document-table">
                    <thead>
                        <tr>
                            <th>Dokumen</th>
                            <th>Jenis</th>
                            <th>Status</th>
                            <th>Skor</th>
                            <th>Versi</th>
                            <th>Diperbarui</th>
                            <th><span class="sr-only">Aksi</span></th>
                        </tr>
                    </thead>
                    <tbody id="documentTableBody"></tbody>
                </table>
            </div>

            <div id="documentEmptyState" class="library-empty hidden">
                <span class="brand-icon">A</span>
                <h2>Belum ada dokumen yang cocok.</h2>
                <p id="documentEmptyMessage">Upload dokumen pertama untuk memulai analisis.</p>
                <a href="/documents/upload" class="primary-button button-link">Upload dokumen</a>
            </div>
        </section>
    </section>

    <div id="editDocumentModal" class="modal-overlay hidden" role="dialog" aria-modal="true" aria-labelledby="editDocumentModalTitle">
        <div class="modal-panel">
            <div class="section-heading section-heading-row">
                <div>
                    <p class="eyebrow">Document metadata</p>
                    <h2 id="editDocumentModalTitle">Edit Dokumen</h2>
                    <p>Perbarui informasi metadata dokumen.</p>
                </div>
                <button type="button" id="closeEditDocumentModal" class="secondary-button">Tutup</button>
            </div>

            <div id="editDocumentAlert" class="form-alert hidden" role="alert"></div>

            <form id="editDocumentForm" class="modal-form">
                <label>
                    <span>Judul</span>
                    <input id="editDocumentTitle" name="title" type="text" required>
                </label>

                <label>
                    <span>Topik / bidang</span>
                    <input id="editDocumentTopic" name="topic" type="text">
                </label>

                <label class="modal-form-wide">
                    <span>Kata kunci</span>
                    <textarea id="editDocumentKeywords" name="keywords" rows="3"></textarea>
                </label>

                <label class="modal-form-wide">
                    <span>Deskripsi</span>
                    <textarea id="editDocumentDescription" name="description" rows="4"></textarea>
                </label>

                <div class="modal-form-actions">
                    <button type="button" id="cancelEditDocument" class="secondary-button">Batal</button>
                    <button type="submit" class="primary-button">
                        <span class="button-label">Simpan Perubahan</span>
                        <span class="button-loader hidden">Menyimpan...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div id="deleteDocumentModal" class="modal-overlay hidden" role="dialog" aria-modal="true" aria-labelledby="deleteDocumentModalTitle">
        <div class="modal-panel modal-panel-sm">
            <div class="section-heading section-heading-row">
                <div>
                    <p class="eyebrow">Konfirmasi</p>
                    <h2 id="deleteDocumentModalTitle">Hapus Dokumen</h2>
                    <p>Apakah Anda yakin ingin menghapus dokumen ini? Semua versi, analisis, dan data terkait akan dihapus permanen.</p>
                </div>
                <button type="button" id="closeDeleteDocumentModal" class="secondary-button">Tutup</button>
            </div>

            <div id="deleteDocumentAlert" class="form-alert hidden" role="alert"></div>

            <div class="confirm-actions">
                <button type="button" id="cancelDeleteDocument" class="secondary-button">Batal</button>
                <button type="button" id="confirmDeleteDocument" class="danger-button">
                    <span class="button-label">Hapus Permanen</span>
                    <span class="button-loader hidden">Menghapus...</span>
                </button>
            </div>
        </div>
    </div>
</main>
@endsection
