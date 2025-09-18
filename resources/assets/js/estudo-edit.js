'use strict';

(function () {
    const estudoId = document.getElementById('estudoId').value;
    const CSRF = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    const FAIXAS_PADRAO = ['0 a 18', '19 a 23', '24 a 28', '29 a 33', '34 a 38', '39 a 43', '44 a 48', '49 a 53', '54 a 58', '59+'];

    const container = document.getElementById('estudosContainer');
    const tpl = document.getElementById('estudoCardTemplate');

    // --------- Helpers ---------
    function moneyBR(n) {
        return Number(n || 0).toLocaleString("pt-BR", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function atualizarTotais() {
        container.querySelectorAll(".estudo").forEach(estudo => {
            let totalVidas = 0;
            let totalGeral = 0;

            estudo.querySelectorAll("tbody tr").forEach(row => {
                const qtde = parseInt(row.querySelector(".qtde")?.value) || 0;
                const valor = parseFloat(row.querySelector(".valor")?.value) || 0;
                const subtotal = qtde * valor;

                row.querySelector(".total").textContent = moneyBR(subtotal);
                totalVidas += qtde;
                totalGeral += subtotal;
            });

            estudo.querySelector(".totalVidas").textContent = totalVidas;
            estudo.querySelector(".totalGeral").textContent = moneyBR(totalGeral);
        });
    }

    function montarLinhaFaixa(faixa, qtde = 0, valor = 0) {
        return `
      <tr>
        <td>${faixa}</td>
        <td><input type="number" class="form-control qtde" min="0" value="${qtde}"></td>
        <td><input type="number" class="form-control valor" step="0.01" min="0" value="${valor}"></td>
        <td class="total">0,00</td>
      </tr>
    `;
    }

    // titulo = header do card (será salvo como estudo_itens.operadora_plano)
    // reembolso = reembolso_consulta
    // faixas = array [{faixa, qtde, valor_unitario}]
    function criarCard({ titulo, coparticipacao = '', categoria = '', reembolso = 0, faixas = [] }) {
        const node = tpl.content.cloneNode(true);

        // Título no header do card
        node.querySelector('.titulo-estudo').textContent = titulo;

        // Campos topo
        node.querySelector('.categoria').value = categoria || '';
        node.querySelector('.coparticipacao').value = coparticipacao || '';
        node.querySelector('.reembolso').value = reembolso || 0;

        // Faixas
        const tbody = node.querySelector('.tbody-faixas');
        if (faixas?.length) {
            faixas.forEach(f => {
                const qt = parseInt(f.qtde) || 0;
                const vu = parseFloat(f.valor_unitario) || 0;
                tbody.insertAdjacentHTML('beforeend', montarLinhaFaixa(f.faixa, qt, vu));
            });
        } else {
            FAIXAS_PADRAO.forEach(faixa => tbody.insertAdjacentHTML('beforeend', montarLinhaFaixa(faixa, 0, 0)));
        }

        container.appendChild(node);
        atualizarTotais();
    }

    // --------- Carregar Planos ao trocar Operadora (para adicionar novos cards) ---------
    document.getElementById("operadoraSelect").addEventListener("change", function () {
        const operadoraId = this.value;
        const planoSelect = document.getElementById("planoSelect");
        planoSelect.innerHTML = "<option value=''>Carregando...</option>";
        planoSelect.disabled = true;

        if (operadoraId) {
            fetch(`/planos/${operadoraId}`)
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

    // --------- Adicionar novo bloco (opcional na edição) ---------
    document.getElementById("addEstudo").addEventListener("click", function () {
        const operadora = document.getElementById("operadoraSelect");
        const plano = document.getElementById("planoSelect");

        if (!operadora.value || !plano.value) {
            alert("Selecione operadora e plano");
            return;
        }

        criarCard({
            titulo: `${operadora.options[operadora.selectedIndex].text} - ${plano.options[plano.selectedIndex].text}`,
            coparticipacao: '',
            reembolso: 0,
            faixas: []
        });
    });

    // --------- Delegação de eventos ---------
    document.addEventListener("input", function (e) {
        if (e.target.classList.contains("qtde") || e.target.classList.contains("valor")) {
            atualizarTotais();
        }
    });

    document.addEventListener("click", function (e) {
        if (e.target.classList.contains("remover-estudo")) {
            e.target.closest(".estudo-wrapper").remove();
        }
    });

    // --------- Carregar estudo existente ---------
    function carregarEstudo() {
        fetch(`/estudos/${estudoId}`)
            .then(res => res.json())
            .then(data => {
                if (!data || !data.id) {
                    alert('Estudo não encontrado');
                    return;
                }

                // Campo de entrada (mantido como nomeEmpresa) recebe estudos.titulo
                document.getElementById('nomeEmpresa').value = data.titulo || '';

                container.innerHTML = '';

                (data.estudos || []).forEach(item => {
                    criarCard({
                        titulo: item.operadora_plano,
                        categoria: item.categoria,
                        coparticipacao: item.coparticipacao,
                        reembolso: item.reembolso_consulta,
                        faixas: (item.vidas || []).map(v => ({
                            faixa: v.faixa,
                            qtde: v.qtde,
                            valor_unitario: v.valor_unitario
                        }))
                    });
                });
            })
            .catch(err => {
                console.error(err);
                alert('Erro ao carregar estudo');
            });
    }

    // --------- Salvar alterações (PUT) ---------
    document.getElementById('salvarEdicao').addEventListener('click', function () {
        const tituloEstudo = document.getElementById('nomeEmpresa').value.trim();
        if (!tituloEstudo) {
            alert('Digite o título do estudo');
            return;
        }

        const estudos = [];

        // Use o wrapper raiz de cada card
        container.querySelectorAll('.estudo-wrapper').forEach(wrapper => {
            // titulo no header
            const operadora_plano = wrapper.querySelector('.titulo-estudo')?.textContent.trim() || '';

            // campos do topo (podem estar fora de .estudo, então busque no wrapper)
            const coparticipacao = (wrapper.querySelector('.coparticipacao')?.value || '').trim();
            const categoria = (wrapper.querySelector('.categoria')?.value || '').trim();
            const reembolso_consulta = parseFloat(wrapper.querySelector('.reembolso')?.value) || 0;

            const vidas = [];
            // linhas da tabela (a tabela geralmente fica dentro de .estudo)
            wrapper.querySelectorAll('tbody tr').forEach(row => {
                const tds = row.children;
                const faixa = tds[0]?.textContent?.trim() || '';
                const qtde = parseInt(tds[1]?.querySelector('input')?.value) || 0;
                const valor_unitario = parseFloat(tds[2]?.querySelector('input')?.value) || 0;
                const total = qtde * valor_unitario;
                vidas.push({ faixa, qtde, valor_unitario, total });
            });

            estudos.push({ operadora_plano, coparticipacao, categoria, reembolso_consulta, vidas });
        });

        fetch(`/estudos/${estudoId}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CSRF
            },
            body: JSON.stringify({ titulo: tituloEstudo, estudos })
        })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert('Estudo atualizado com sucesso!');
                    window.location.href = "/estudo-lista";
                } else {
                    alert(data.message || 'Erro ao atualizar estudo.');
                }
            })
            .catch(err => {
                console.error(err);
                alert('Erro ao atualizar estudo.');
            });
    });


    // init
    carregarEstudo();
})();
