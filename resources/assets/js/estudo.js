'use strict';

(function () {

    // Função para atualizar totais (igual à sua)
    function atualizarTotais() {
        document.querySelectorAll(".estudo").forEach(estudo => {
            let totalVidas = 0;
            let totalGeral = 0;

            estudo.querySelectorAll("tbody tr").forEach(row => {
                const qtde = parseInt(row.querySelector(".qtde").value) || 0;
                const valor = parseFloat(row.querySelector(".valor").value) || 0;
                const subtotal = qtde * valor;

                row.querySelector(".total").textContent = subtotal.toLocaleString("pt-BR", { minimumFractionDigits: 2 });

                totalVidas += qtde;
                totalGeral += subtotal;
            });

            estudo.querySelector(".totalVidas").textContent = totalVidas;
            estudo.querySelector(".totalGeral").textContent = totalGeral.toLocaleString("pt-BR", { minimumFractionDigits: 2 });
        });
    }

    // Carregar planos ao selecionar operadora
    document.getElementById("operadoraSelect").addEventListener("change", function () {
        const operadoraId = this.value;
        const planoSelect = document.getElementById("planoSelect");

        planoSelect.innerHTML = "<option value=''>Carregando...</option>";
        planoSelect.disabled = true;

        if (operadoraId) {
            fetch(`planos/${operadoraId}`)
                .then(res => res.json())
                .then(planos => {
                    planoSelect.innerHTML = "<option value=''>Selecione</option>";
                    planos.forEach(plano => {
                        planoSelect.innerHTML += `<option value="${plano.id}">${plano.nome}</option>`;
                    });
                    planoSelect.disabled = false;
                });
        }
    });

    // Adicionar bloco de estudo
    document.getElementById("addEstudo").addEventListener("click", function () {
        const operadora = document.getElementById("operadoraSelect");
        const plano = document.getElementById("planoSelect");

        if (!operadora.value || !plano.value) {
            alert("Selecione operadora e plano");
            return;
        }

        const container = document.getElementById("estudosContainer");
        const estudoId = `estudo_${Date.now()}`;
        const faixas = ['0 a 18', '19 a 23', '24 a 28', '29 a 33', '34 a 38', '39 a 43', '44 a 48', '49 a 53', '54 a 58', '59+'];

        let html = `
        <div class="card mb-4 estudo" id="${estudoId}">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0">${operadora.options[operadora.selectedIndex].text} - ${plano.options[plano.selectedIndex].text}</h6>
                <button type="button" class="btn btn-sm btn-danger remover-estudo">Remover</button>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col">
                        <label>Categoria</label>
                        <input type="text" class="form-control categoria">
                    </div>
                    <div class="col">
                        <label>Coparticipação</label>
                        <input type="text" class="form-control coparticipacao">
                    </div>
                    <div class="col">
                        <label>Reembolso Consulta (R$)</label>
                        <input type="number" class="form-control reembolso" step="0.01" min="0" value="0">
                    </div>
                </div>
                <table class="table table-bordered text-center align-middle">
                    <thead>
                        <tr>
                            <th>Faixa Etária</th>
                            <th>Qtde Vidas</th>
                            <th>Valor Unitário (R$)</th>
                            <th>Total (R$)</th>
                        </tr>
                    </thead>
                    <tbody>`;

        faixas.forEach(faixa => {
            html += `
                <tr>
                    <td>${faixa}</td>
                    <td><input type="number" class="form-control qtde" min="0" value="0"></td>
                    <td><input type="number" class="form-control valor" step="0.01" min="0" value="0"></td>
                    <td class="total">0,00</td>
                </tr>`;
        });

        html += `
                    </tbody>
                    <tfoot>
                        <tr class="table-secondary">
                            <th>Total</th>
                            <th class="totalVidas">0</th>
                            <th></th>
                            <th class="totalGeral">0,00</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>`;

        container.insertAdjacentHTML("beforeend", `<div class="col-md-4">${html}</div>`);

        atualizarTotais();
    });

    // Delegação de eventos para inputs e remover
    document.addEventListener("input", function (e) {
        if (e.target.classList.contains("qtde") || e.target.classList.contains("valor")) {
            atualizarTotais();
        }
    });

    document.addEventListener("click", function (e) {
        if (e.target.classList.contains("remover-estudo")) {
            e.target.closest(".col-md-6").remove();
        }
    });

    // Botão salvar estudo
    const saveBtn = document.createElement('button');
    saveBtn.classList.add('btn', 'btn-success', 'mt-3');
    saveBtn.textContent = "Salvar Estudo";
    document.querySelector('.card-body').appendChild(saveBtn);

    saveBtn.addEventListener('click', function () {
        const nomeEmpresa = document.getElementById('nomeEmpresa').value.trim();
        if (!nomeEmpresa) {
            alert("Digite o nome da empresa");
            return;
        }

        const estudos = [];
        document.querySelectorAll('.estudo').forEach(card => {
            const titulo = card.querySelector('h6').textContent;
            const coparticipacao = card.querySelector('.coparticipacao').value;
            const categoria = card.querySelector('.categoria').value;
            const reembolso = parseFloat(card.querySelector('.reembolso').value) || 0;
            const faixas = [];

            card.querySelectorAll('tbody tr').forEach(row => {
                faixas.push({
                    faixa: row.children[0].textContent,
                    qtde: parseInt(row.children[1].querySelector('input').value) || 0,
                    valor_unitario: parseFloat(row.children[2].querySelector('input').value) || 0,
                    total: parseFloat(row.children[3].textContent.replace(/\./g, '').replace(',', '.')) || 0
                });
            });

            estudos.push({
                titulo,
                coparticipacao,
                categoria,
                reembolso,
                faixas
            });
        });

        fetch('/estudos', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                nome_empresa: nomeEmpresa,
                estudos
            })
        })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert('Estudo salvo com sucesso!');
                    window.location.href = "/estudo-lista";
                } else {
                    alert('Erro ao salvar estudo.');
                }
            })
            .catch(err => console.error(err));
    });

})();
