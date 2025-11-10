<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Metas Diárias - TV Comercial</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
            color: #fff;
            padding: 20px;
            min-height: 100vh;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            background: rgba(255, 255, 255, 0.1);
            padding: 20px;
            border-radius: 15px;
            backdrop-filter: blur(10px);
        }

        .header h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }

        .header .data {
            font-size: 1.3em;
            opacity: 0.9;
        }

        .metas-table {
            width: 100%;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            overflow: hidden;
            backdrop-filter: blur(10px);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: rgba(0, 0, 0, 0.3);
        }

        th {
            padding: 20px;
            text-align: left;
            font-size: 1.3em;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        th.center {
            text-align: center;
        }

        tbody tr {
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            transition: background 0.3s ease;
        }

        tbody tr:hover {
            background: rgba(255, 255, 255, 0.05);
        }

        tbody tr:last-child {
            border-bottom: none;
        }

        td {
            padding: 20px;
            font-size: 1.4em;
        }

        td.center {
            text-align: center;
        }

        .vendedor-nome {
            font-weight: 700;
            font-size: 1.1em;
        }

        .numero {
            font-weight: 900;
            font-size: 1.3em;
        }

        .meta {
            color: #fbbf24;
        }

        .realizado {
            color: #4ade80;
        }

        .percentual {
            font-weight: 900;
            font-size: 1.5em;
        }

        .footer {
            text-align: center;
            margin-top: 30px;
            font-size: 1.1em;
            opacity: 0.8;
        }

        .loading {
            text-align: center;
            font-size: 2em;
            margin-top: 100px;
        }

        .no-data {
            text-align: center;
            font-size: 2em;
            margin-top: 100px;
            padding: 40px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
        }

        .posicao {
            font-weight: 900;
            font-size: 1.5em;
            color: #fbbf24;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>📊 METAS DIÁRIAS</h1>
        <div class="data" id="data-atual">Carregando...</div>
    </div>

    <div id="loading" class="loading">
        Carregando dados...
    </div>

    <div id="content" style="display: none;">
        <div class="metas-table">
            <table>
                <thead>
                    <tr>
                        <th width="10%" class="center">#</th>
                        <th width="50%">VENDEDOR</th>
                        <th width="15%" class="center">META</th>
                        <th width="15%" class="center">REALIZADO</th>
                        <th width="10%" class="center">%</th>
                    </tr>
                </thead>
                <tbody id="tbody-metas">
                    <!-- Dados serão inseridos aqui -->
                </tbody>
            </table>
        </div>

        <div class="footer">
            🔄 Atualização automática a cada 30 segundos • Última atualização: <span id="ultima-atualizacao">-</span>
        </div>
    </div>

    <div id="no-data" class="no-data" style="display: none;">
        Nenhuma meta cadastrada para hoje
    </div>

    <script>
        const urlParams = new URLSearchParams(window.location.search);
        const empresaId = urlParams.get('empresa_id');
        const API_URL = '/tv-comercial/dados?empresa_id=' + empresaId;
        const REFRESH_INTERVAL = 30000; // 30 segundos

        async function carregarDados() {
            try {
                const response = await fetch(API_URL);
                const dados = await response.json();

                if (dados.error) {
                    mostrarErro(dados.error);
                    return;
                }

                atualizarInterface(dados);
            } catch (error) {
                console.error('Erro ao carregar dados:', error);
            }
        }

        function atualizarInterface(dados) {
            document.getElementById('loading').style.display = 'none';

            if (!dados.metas || dados.metas.length === 0) {
                document.getElementById('content').style.display = 'none';
                document.getElementById('no-data').style.display = 'block';
                return;
            }

            document.getElementById('content').style.display = 'block';
            document.getElementById('no-data').style.display = 'none';

            // Atualizar cabeçalho
            document.getElementById('data-atual').textContent = dados.data_formatada;

            // Atualizar tabela
            const tbody = document.getElementById('tbody-metas');
            tbody.innerHTML = '';

            dados.metas.forEach((meta, index) => {
                const tr = document.createElement('tr');

                tr.innerHTML = `
                    <td class="center">
                        <span class="posicao">${String(index + 1).padStart(2, '0')}</span>
                    </td>
                    <td>
                        <span class="vendedor-nome">${meta.vendedor}</span>
                    </td>
                    <td class="center">
                        <span class="numero meta">${meta.meta_cotacoes}</span>
                    </td>
                    <td class="center">
                        <span class="numero realizado">${meta.cotacoes_realizadas}</span>
                    </td>
                    <td class="center">
                        <span class="percentual">${meta.percentual_concluido.toFixed(1)}%</span>
                    </td>
                `;

                tbody.appendChild(tr);
            });

            // Atualizar última atualização
            document.getElementById('ultima-atualizacao').textContent = dados.ultima_atualizacao;
        }

        function mostrarErro(mensagem) {
            document.getElementById('loading').style.display = 'none';
            document.getElementById('content').style.display = 'none';
            const noData = document.getElementById('no-data');
            noData.textContent = mensagem;
            noData.style.display = 'block';
        }

        // Inicializar
        if (!empresaId) {
            mostrarErro('ID da empresa não informado. Use ?empresa_id=X na URL');
        } else {
            carregarDados();
            setInterval(carregarDados, REFRESH_INTERVAL);
        }
    </script>
</body>
</html>
