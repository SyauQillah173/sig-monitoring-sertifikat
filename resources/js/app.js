const initializeWorkspaceSidebar = () => {
    const body = document.body;
    const collapseStorageKey = 'ui-workspace-sidebar-collapsed';

    if (!body) {
        return;
    }

    if (window.__workspaceSidebarCleanup instanceof Function) {
        window.__workspaceSidebarCleanup();
    }

    const controller = new AbortController();
    const { signal } = controller;
    const desktopQuery = window.matchMedia('(min-width: 1024px)');
    const isDesktop = () => desktopQuery.matches;

    window.__workspaceSidebarCleanup = () => controller.abort();

    const getStoredCollapsed = () => {
        try {
            return window.localStorage.getItem(collapseStorageKey) === 'true';
        } catch (error) {
            return false;
        }
    };

    const storeCollapsed = (collapsed) => {
        try {
            window.localStorage.setItem(collapseStorageKey, collapsed ? 'true' : 'false');
        } catch (error) {
            return;
        }
    };

    const openSidebar = () => body.setAttribute('data-sidebar-open', 'true');
    const closeSidebar = () => body.setAttribute('data-sidebar-open', 'false');

    const setCollapsed = (collapsed) => {
        body.setAttribute('data-sidebar-collapsed', collapsed ? 'true' : 'false');
        storeCollapsed(collapsed);
    };

    const syncSidebarMode = () => {
        if (isDesktop()) {
            closeSidebar();
            body.setAttribute('data-sidebar-collapsed', getStoredCollapsed() ? 'true' : 'false');

            return;
        }

        closeSidebar();
        body.setAttribute('data-sidebar-collapsed', 'false');
    };

    const toggleSidebar = () => {
        if (isDesktop()) {
            setCollapsed(body.getAttribute('data-sidebar-collapsed') !== 'true');

            return;
        }

        if (body.getAttribute('data-sidebar-open') === 'true') {
            closeSidebar();

            return;
        }

        openSidebar();
    };

    syncSidebarMode();

    document.querySelectorAll('[data-sidebar-toggle]').forEach((button) => {
        button.addEventListener('click', toggleSidebar, { signal });
    });

    document.querySelectorAll('[data-sidebar-close]').forEach((button) => {
        button.addEventListener('click', closeSidebar, { signal });
    });

    document.querySelectorAll('.ui-workspace-sidebar a').forEach((link) => {
        link.addEventListener('click', () => {
            if (!isDesktop()) {
                closeSidebar();
            }
        }, { signal });
    });

    window.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeSidebar();
        }
    }, { signal });

    window.addEventListener('resize', syncSidebarMode, { signal });
    window.addEventListener('orientationchange', syncSidebarMode, { signal });

    if (desktopQuery.addEventListener instanceof Function) {
        desktopQuery.addEventListener('change', syncSidebarMode, { signal });
    }
};

const initializeNavigationEditor = () => {
    document.querySelectorAll('[data-navigation-editor]').forEach((root) => {
        if (root.dataset.navigationEditorReady === '1') {
            return;
        }

        root.dataset.navigationEditorReady = '1';

        const tableBody = root.querySelector('tbody');
        let draggedCard = null;

        const field = (index, name) => root.querySelector(`[data-nav-field="${name}"][data-nav-index="${index}"]`);
        const row = (index) => root.querySelector(`[data-nav-row][data-nav-index="${index}"]`);

        const syncLaneCounts = () => {
            root.querySelectorAll('[data-nav-lane]').forEach((lane) => {
                const counter = lane.parentElement?.querySelector('[data-nav-count]');

                if (counter) {
                    counter.textContent = `${lane.querySelectorAll('[data-nav-card]').length} menu`;
                }
            });
        };

        const syncTableFromBoard = () => {
            let nextOrder = 10;

            root.querySelectorAll('[data-nav-lane]').forEach((lane) => {
                lane.querySelectorAll('[data-nav-card]').forEach((card) => {
                    const index = card.dataset.navIndex;
                    const groupInput = field(index, 'group_label');
                    const orderInput = field(index, 'sort_order');
                    const tableRow = row(index);

                    if (groupInput) {
                        groupInput.value = lane.dataset.groupLabel;
                    }

                    if (orderInput) {
                        orderInput.value = nextOrder;
                    }

                    if (tableBody && tableRow) {
                        tableBody.appendChild(tableRow);
                    }

                    nextOrder += 10;
                });
            });

            syncLaneCounts();
        };

        root.querySelectorAll('[data-nav-card]').forEach((card) => {
            card.addEventListener('dragstart', () => {
                draggedCard = card;
                card.classList.add('opacity-60');
            });

            card.addEventListener('dragend', () => {
                card.classList.remove('opacity-60');
                draggedCard = null;
                syncTableFromBoard();
            });
        });

        root.querySelectorAll('[data-nav-field="label"]').forEach((input) => {
            input.addEventListener('input', () => {
                const label = root.querySelector(`[data-nav-card][data-nav-index="${input.dataset.navIndex}"] [data-nav-card-label]`);

                if (label) {
                    label.textContent = input.value || 'Tanpa label';
                }
            });
        });

        root.querySelectorAll('[data-nav-icon-radio]').forEach((radio) => {
            radio.addEventListener('change', () => {
                const card = radio.closest('[data-nav-card]');
                const label = card?.querySelector('[data-nav-icon-label]');
                const preview = card?.querySelector('[data-nav-icon-preview]');
                const icon = radio.closest('label')?.querySelector('[data-nav-icon-option]');

                if (label) {
                    label.textContent = radio.dataset.navIconLabelValue || radio.value;
                }

                if (preview && icon) {
                    preview.innerHTML = icon.innerHTML;
                }
            });
        });

        root.addEventListener('dragover', (event) => {
            if (!draggedCard) {
                return;
            }

            const lane = event.target.closest('[data-nav-lane]');

            if (!lane) {
                return;
            }

            event.preventDefault();

            const targetCard = event.target.closest('[data-nav-card]');

            if (targetCard && targetCard !== draggedCard && lane.contains(targetCard)) {
                const box = targetCard.getBoundingClientRect();
                const shouldPlaceBefore = event.clientY < box.top + (box.height / 2);

                lane.insertBefore(draggedCard, shouldPlaceBefore ? targetCard : targetCard.nextSibling);

                return;
            }

            if (!targetCard && lane !== draggedCard.parentElement) {
                lane.appendChild(draggedCard);
            }
        });

        root.addEventListener('drop', (event) => {
            if (!draggedCard) {
                return;
            }

            event.preventDefault();
            syncTableFromBoard();
        });
    });
};

const initializeSmartForms = () => {
    if (window.__smartFormsInitialized) {
        return;
    }

    window.__smartFormsInitialized = true;

    const state = {
        form: null,
        submitter: null,
        previousFocus: null,
    };

    const getSubmitter = (event) => {
        if (event?.submitter instanceof HTMLElement) {
            return event.submitter;
        }

        if (document.activeElement instanceof HTMLElement && document.activeElement.matches('button, input[type="submit"]')) {
            return document.activeElement;
        }

        return null;
    };

    const isMutatingForm = (form) => (form.getAttribute('method') || 'GET').toLowerCase() !== 'get';

    const inferLoadingLabel = (form, submitter) => {
        const explicit = submitter?.dataset.loadingLabel || form.dataset.confirmLoadingLabel || form.dataset.loadingLabel;

        if (explicit) {
            return explicit;
        }

        const text = (submitter?.textContent || '').trim().toLowerCase();

        if (text.includes('hapus')) {
            return 'Menghapus...';
        }

        if (text.includes('backup')) {
            return 'Membuat backup...';
        }

        if (text.includes('cleanup') || text.includes('bersih')) {
            return 'Membersihkan...';
        }

        if (text.includes('reset')) {
            return 'Mereset...';
        }

        if (text.includes('simpan')) {
            return 'Menyimpan...';
        }

        if (text.includes('keluar')) {
            return 'Keluar...';
        }

        return 'Memproses...';
    };

    const setButtonLoading = (button, label) => {
        if (!(button instanceof HTMLElement) || button.dataset.loadingActive === '1') {
            return;
        }

        button.dataset.loadingActive = '1';
        button.dataset.originalLabel = button.textContent.trim();
        button.setAttribute('aria-disabled', 'true');
        button.classList.add('ui-button-loading');

        if ('disabled' in button) {
            button.disabled = true;
        }

        if (button instanceof HTMLInputElement) {
            button.value = label;

            return;
        }

        button.replaceChildren();

        const spinner = document.createElement('span');
        spinner.className = 'ui-loading-spinner';
        spinner.setAttribute('aria-hidden', 'true');

        const labelNode = document.createElement('span');
        labelNode.textContent = label;

        button.append(spinner, labelNode);
    };

    const setFormLoading = (form, submitter) => {
        form.classList.add('ui-is-loading');
        form.setAttribute('aria-busy', 'true');

        const primaryButton = submitter || form.querySelector('button[type="submit"], input[type="submit"], button:not([type])');
        const loadingLabel = inferLoadingLabel(form, primaryButton);

        setButtonLoading(primaryButton, loadingLabel);

        form.querySelectorAll('button[type="submit"], input[type="submit"], button:not([type])').forEach((button) => {
            if (button !== primaryButton && 'disabled' in button) {
                button.disabled = true;
                button.setAttribute('aria-disabled', 'true');
            }
        });
    };

    const getModal = () => document.querySelector('[data-confirm-modal]');

    const closeModal = () => {
        const modal = getModal();

        if (!modal) {
            return;
        }

        modal.hidden = true;
        modal.classList.remove('is-open');
        document.body.classList.remove('ui-confirm-open');

        if (state.previousFocus instanceof HTMLElement) {
            state.previousFocus.focus({ preventScroll: true });
        }

        state.form = null;
        state.submitter = null;
        state.previousFocus = null;
    };

    const openModal = (form, submitter) => {
        const modal = getModal();

        if (!modal) {
            return false;
        }

        state.form = form;
        state.submitter = submitter;
        state.previousFocus = document.activeElement;

        const title = form.dataset.confirmTitle || 'Konfirmasi';
        const message = form.dataset.confirmMessage || 'Lanjutkan aksi ini?';
        const action = form.dataset.confirmAction || 'Lanjutkan';
        const tone = form.dataset.confirmTone || (action.toLowerCase().includes('hapus') ? 'danger' : 'default');

        modal.dataset.tone = tone;
        modal.querySelector('[data-confirm-title]').textContent = title;
        modal.querySelector('[data-confirm-message]').textContent = message;
        modal.querySelector('[data-confirm-approve]').textContent = action;

        const eyebrow = modal.querySelector('[data-confirm-eyebrow]');

        if (eyebrow) {
            eyebrow.textContent = tone === 'danger' ? 'Aksi membutuhkan konfirmasi' : 'Konfirmasi aksi';
        }

        modal.hidden = false;
        requestAnimationFrame(() => modal.classList.add('is-open'));
        document.body.classList.add('ui-confirm-open');
        modal.querySelector('[data-confirm-cancel]')?.focus({ preventScroll: true });

        return true;
    };

    document.addEventListener('submit', (event) => {
        const form = event.target;

        if (!(form instanceof HTMLFormElement) || !isMutatingForm(form)) {
            return;
        }

        const submitter = getSubmitter(event);

        if (form.matches('[data-confirm]') && form.dataset.confirmApproved !== 'true') {
            event.preventDefault();
            openModal(form, submitter);

            return;
        }

        if (form.dataset.confirmApproved === 'true') {
            delete form.dataset.confirmApproved;
        }

        setFormLoading(form, submitter);
    });

    document.addEventListener('click', (event) => {
        if (event.target.closest('[data-confirm-cancel]')) {
            closeModal();

            return;
        }

        if (!event.target.closest('[data-confirm-approve]') || !state.form) {
            return;
        }

        const form = state.form;
        const submitter = state.submitter;

        form.dataset.confirmApproved = 'true';
        closeModal();

        if (form.requestSubmit instanceof Function) {
            try {
                form.requestSubmit(submitter);

                return;
            } catch (error) {
                form.requestSubmit();

                return;
            }
        }

        setFormLoading(form, submitter);
        form.submit();
    });

    window.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && getModal()?.classList.contains('is-open')) {
            closeModal();
        }
    });
};

document.addEventListener('DOMContentLoaded', initializeWorkspaceSidebar);
document.addEventListener('livewire:navigated', initializeWorkspaceSidebar);
document.addEventListener('DOMContentLoaded', initializeNavigationEditor);
document.addEventListener('livewire:navigated', initializeNavigationEditor);
document.addEventListener('DOMContentLoaded', initializeSmartForms);
document.addEventListener('livewire:navigated', initializeSmartForms);
