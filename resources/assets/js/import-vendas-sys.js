'use strict';

(function () {
    const form = document.getElementById('formImportVendas');
    const btnImportar = document.getElementById('btnImportar');
    const cardResultado = document.getElementById('cardResultado');

    if (!form) return;

    form.addEventListener('submit', async function (e) {
        e.preventDefault();

        const fileInput = document.getElementById('arquivo');
        if (!fileInput.files.length) {
            toastr.error('Selecione um arquivo CSV');
            return;
        }

        const formData = new FormData(form);

        btnImportar.disabled = true;
        btnImportar.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Importando...';

        try {
            const response = await fetch('/vendas/import-sys', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                }
            });

            const data = await response.json();

            if (data.success) {
                toastr.success(data.message);
                displayResults(data);
            } else {
                toastr.error(data.message || 'Erro ao importar arquivo');
            }
        } catch (error) {
            console.error('Erro:', error);
            toastr.error('Erro ao processar importacao');
        } finally {
            btnImportar.disabled = false;
            btnImportar.innerHTML = '<i class="ti ti-upload me-1"></i> Importar Vendas';
        }
    });

    function displayResults(data) {
        cardResultado.classList.remove('d-none');

        // Update counters
        document.getElementById('totalImportados').textContent = data.resumo.total_importados || 0;
        document.getElementById('totalDuplicados').textContent = data.resumo.total_duplicados || 0;
        document.getElementById('totalErros').textContent = data.resumo.total_erros || 0;
        document.getElementById('totalWarnings').textContent = data.resumo.total_warnings || 0;

        // Populate tables
        populateTable('tbodyImportados', data.importados || []);
        populateTable('tbodyDuplicados', data.duplicados || []);
        populateTable('tbodyErros', data.erros || []);
        populateTable('tbodyWarnings', data.warnings || []);

        // Scroll to results
        cardResultado.scrollIntoView({ behavior: 'smooth' });
    }

    function populateTable(tbodyId, items) {
        const tbody = document.getElementById(tbodyId);
        tbody.innerHTML = '';

        if (items.length === 0) {
            tbody.innerHTML = '<tr><td class="text-muted text-center">Nenhum registro</td></tr>';
            return;
        }

        items.forEach(item => {
            const tr = document.createElement('tr');
            tr.innerHTML = `<td>${escapeHtml(item)}</td>`;
            tbody.appendChild(tr);
        });
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
})();
