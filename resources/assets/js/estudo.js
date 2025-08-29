'use strict';

(function () {

    // Listener para carregar planos ao selecionar operadora
    document.getElementById("operadoraSelect").addEventListener("change", function () {
        const operadoraId = this.value;
        const planoSelect = document.getElementById("planoSelect");

        planoSelect.innerHTML = "<option value=''>Carregando...</option>";
        planoSelect.disabled = true;

        if (operadoraId) {
            fetch(`planos/${operadoraId}`) // rota que você vai criar
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

    // Listener para adicionar bloco de estudo
    document.getElementById("addEstudo").addEventListener("click", function () {
        const operadora = document.getElementById("operadoraSelect");
        const plano = document.getElementById("planoSelect");

        if (!operadora.value || !plano.value) {
            alert("Selecione operadora e plano");
            return;
        }

        const container = document.getElementById("estudosContainer");

        const estudoId = `estudo_${Date.now()}`;
        const faixas = ['0 a 18', '19 a 23', '24 a 28', '29 a 33', '34 a 38', '39 a 43', '49 a 53', '54 a 58', '59+'];

        let html = `
        <div class="card mb-4 estudo" id="${estudoId}">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0">${operadora.options[operadora.selectedIndex].text} - ${plano.options[plano.selectedIndex].text}</h6>
                <button type="button" class="btn btn-sm btn-danger remover-estudo">Remover</button>
            </div>

            <!-- Novos campos abaixo do botão de remover -->
            <div class="card-body">
                <div class="row mb-3">
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

        atualizarTotais(); // recalcula os valores
    });

    // Delegação de eventos para inputs e remover
    document.addEventListener("input", function (e) {
        if (e.target.classList.contains("qtde") || e.target.classList.contains("valor")) {
            atualizarTotais();
        }
    });

    document.addEventListener("click", function (e) {
        if (e.target.classList.contains("remover-estudo")) {
            e.target.closest(".col-md-4").remove(); // remove a coluna inteira
        }
    });

    // Função para atualizar totais de cada bloco
    function atualizarTotais() {
        document.querySelectorAll(".estudo").forEach(estudo => {
            let totalVidas = 0;
            let totalGeral = 0;

            estudo.querySelectorAll("tbody tr").forEach(row => {
                const qtde = parseInt(row.querySelector(".qtde").value) || 0;
                const valor = parseFloat(row.querySelector(".valor").value) || 0;
                const subtotal = qtde * valor;

                row.querySelector(".total").textContent = subtotal.toLocaleString("pt-BR", {
                    minimumFractionDigits: 2
                });

                totalVidas += qtde;
                totalGeral += subtotal;
            });

            estudo.querySelector(".totalVidas").textContent = totalVidas;
            estudo.querySelector(".totalGeral").textContent = totalGeral.toLocaleString("pt-BR", {
                minimumFractionDigits: 2
            });
        });
    }

})();
