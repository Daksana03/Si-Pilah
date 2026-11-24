document.addEventListener('DOMContentLoaded', () => {
    // 1. Navigasi Tab Interaktif
    const tabButtons = document.querySelectorAll('.tab-btn');
    const tabPanels = document.querySelectorAll('.tab-panel');

    tabButtons.forEach((btn) => {
        btn.addEventListener('click', () => {
            const targetId = btn.getAttribute('data-target');

            tabButtons.forEach((b) => b.classList.remove('active'));
            tabPanels.forEach((p) => p.classList.remove('active'));

            btn.classList.add('active');
            const targetPanel = document.getElementById(targetId);
            if (targetPanel) {
                targetPanel.classList.add('active');
            }
        });
    });

    // 2. Konfirmasi sebelum menukar saldo menjadi voucher komoditas
    const redeemForms = document.querySelectorAll('.redeem-form');
    redeemForms.forEach((form) => {
        form.addEventListener('submit', (e) => {
            const name = form.getAttribute('data-name');
            const price = Number(form.getAttribute('data-price'));
            const qty = Number(form.querySelector('input[name="quantity"]').value || 1);
            const total = (price * qty).toLocaleString('id-ID');

            const confirmed = confirm(
                `Tukar saldo Anda sebesar Rp ${total} untuk ${qty}x ${name}?`
            );

            if (!confirmed) {
                e.preventDefault();
            }
        });
    });
});