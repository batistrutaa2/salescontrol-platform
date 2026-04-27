'use strict';

(function () {
    const cfg = window.lkbConverter || {};
    const form = document.getElementById('lkb-form-converter');
    if (!form) return;

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const btn = document.getElementById('lkb-btn-converter');
        const data = new FormData(form);
        const payload = {};
        data.forEach((v, k) => { if (v !== '' && k !== '_token') payload[k] = v; });

        btn.disabled = true;
        try {
            const resp = await fetch(cfg.submitUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': cfg.csrf,
                    Accept: 'application/json',
                },
                body: JSON.stringify(payload),
            });
            const body = await resp.json();
            if (!resp.ok) {
                alert(body.error || (body.errors ? Object.values(body.errors).flat().join('\n') : 'Falha ao criar contrato.'));
                return;
            }
            window.location.href = body.redirect;
        } finally {
            btn.disabled = false;
        }
    });
})();
