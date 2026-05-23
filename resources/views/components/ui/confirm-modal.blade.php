<div class="ui-confirm-modal" data-confirm-modal hidden>
    <div class="ui-confirm-backdrop" data-confirm-cancel></div>

    <section
        class="ui-confirm-dialog"
        role="dialog"
        aria-modal="true"
        aria-labelledby="ui-confirm-title"
        aria-describedby="ui-confirm-message"
    >
        <div class="ui-confirm-icon" data-confirm-icon>
            <flux:icon name="trash" variant="outline" class="size-5" />
        </div>

        <div class="min-w-0">
            <p class="ui-confirm-eyebrow" data-confirm-eyebrow>Konfirmasi aksi</p>
            <h2 id="ui-confirm-title" class="ui-confirm-title" data-confirm-title>Konfirmasi</h2>
            <p id="ui-confirm-message" class="ui-confirm-message" data-confirm-message>
                Lanjutkan aksi ini?
            </p>
        </div>

        <div class="ui-confirm-actions">
            <button type="button" class="ui-button-secondary ui-confirm-cancel" data-confirm-cancel>
                Batal
            </button>
            <button type="button" class="ui-button-danger ui-confirm-approve" data-confirm-approve>
                Hapus
            </button>
        </div>
    </section>
</div>
