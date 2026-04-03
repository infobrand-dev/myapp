<div class="modal fade" id="start-conversation-modal" tabindex="-1" aria-labelledby="start-conversation-modal-label" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ route('conversations.start') }}">
                @csrf
                <div class="modal-header">
                    <h3 class="modal-title fs-5 mb-0" id="start-conversation-modal-label">Mulai Percakapan</h3>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <label for="start-user-picker" class="form-label">Pilih pengguna</label>
                    <div class="user-search-wrap">
                        <input
                            type="text"
                            id="start-user-picker"
                            class="form-control"
                            placeholder="Cari nama atau email..."
                            autocomplete="off"
                            required>
                        <div id="start-user-results" class="user-search-results"></div>
                    </div>
                    <input type="hidden" name="query" id="start-user-id" required>
                    <div id="start-user-invalid" class="text-danger small mt-2 d-none">Pilih pengguna dari daftar hasil pencarian.</div>
                    <div class="text-muted small mt-2">Ketik minimal 2 karakter untuk mencari pengguna.</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Mulai</button>
                </div>
            </form>
        </div>
    </div>
</div>
