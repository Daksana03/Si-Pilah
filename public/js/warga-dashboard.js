document.addEventListener('DOMContentLoaded', () => {
    // 1. Navigasi Tab Interaktif (tab aktif diingat per halaman)
    const tabButtons = document.querySelectorAll('.tab-btn');
    const tabPanels = document.querySelectorAll('.tab-panel');
    const tabStorageKey = 'activeTab:' + window.location.pathname;

    const activateTab = (targetId) => {
        tabButtons.forEach((b) => b.classList.remove('active'));
        tabPanels.forEach((p) => p.classList.remove('active'));

        const button = document.querySelector('.tab-btn[data-target="' + targetId + '"]');
        const panel = document.getElementById(targetId);

        if (button && panel) {
            button.classList.add('active');
            panel.classList.add('active');
            localStorage.setItem(tabStorageKey, targetId);
        }
    };

    tabButtons.forEach((btn) => {
        btn.addEventListener('click', () => activateTab(btn.getAttribute('data-target')));
    });

    // pulihkan tab terakhir setelah redirect/refresh
    const storedTab = localStorage.getItem(tabStorageKey);
    if (storedTab && document.getElementById(storedTab)) {
        activateTab(storedTab);
    }

    // 2. Konfirmasi penukaran saldo lewat modal (bukan alert bawaan browser)
    const modal = document.getElementById('redeemModal');

    if (!modal) {
        return;
    }

    const titleEl = modal.querySelector('[data-modal-title]');
    const detailEl = modal.querySelector('[data-modal-detail]');
    const confirmBtn = modal.querySelector('[data-modal-confirm]');
    const cancelBtn = modal.querySelector('[data-modal-cancel]');
    let pendingForm = null;
    let lastFocused = null;

    const openModal = (form) => {
        const name = form.getAttribute('data-name');
        const price = Number(form.getAttribute('data-price'));
        const qty = Number(form.querySelector('input[name="quantity"]').value || 1);

        titleEl.textContent = 'Tukar saldo menjadi ' + name + '?';
        detailEl.textContent = qty + 'x ' + name + ' — Rp ' + (price * qty).toLocaleString('id-ID');

        pendingForm = form;
        lastFocused = document.activeElement;
        modal.hidden = false;
        modal.classList.add('is-open');
        confirmBtn.focus();
    };

    const closeModal = () => {
        modal.classList.remove('is-open');
        modal.hidden = true;
        pendingForm = null;
        if (lastFocused) {
            lastFocused.focus();
        }
    };

    document.querySelectorAll('.redeem-form').forEach((form) => {
        form.addEventListener('submit', (e) => {
            // lanjutkan submit setelah pengguna menekan "Ya, Tukarkan"
            if (form.dataset.confirmed === '1') {
                delete form.dataset.confirmed;
                return;
            }

            e.preventDefault();
            openModal(form);
        });
    });

    confirmBtn.addEventListener('click', () => {
        const form = pendingForm;
        closeModal();

        if (form) {
            form.dataset.confirmed = '1';
            form.requestSubmit();
        }
    });

    cancelBtn.addEventListener('click', closeModal);

    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            closeModal();
        }
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !modal.hidden) {
            closeModal();
        }
    });
});
