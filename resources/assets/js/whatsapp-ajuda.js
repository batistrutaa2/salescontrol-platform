/**
 * WhatsApp - Central de ajuda: diagnóstico ao vivo do ambiente do vendedor.
 * Testa conexão da instância, WebSocket (tempo real), som e microfone.
 */

'use strict';

(function () {
  const pagina = document.querySelector('.wa-ajuda');
  if (!pagina || pagina.dataset.podeConectar !== '1') return;

  const btnRodar = document.getElementById('wa-diag-rodar');

  function setStatus(id, estado, detalhe) {
    // estado: 'ok' | 'atencao' | 'erro' | 'testando'
    const item = document.getElementById(id);
    if (!item) return;

    item.dataset.estado = estado;

    const icones = {
      ok: '<i class="ri-checkbox-circle-fill"></i>',
      atencao: '<i class="ri-error-warning-fill"></i>',
      erro: '<i class="ri-close-circle-fill"></i>',
      testando: '<span class="spinner-border spinner-border-sm"></span>'
    };

    item.querySelector('.wa-diag-status').innerHTML = icones[estado] || '';
    item.querySelector('.wa-diag-detalhe').innerHTML = detalhe;
  }

  async function testarConexao() {
    setStatus('diag-conexao', 'testando', 'Consultando...');

    try {
      const response = await fetch('/whatsapp/conexao/status');
      const json = await response.json();
      const status = json.data?.status;
      const numero = json.data?.numero_conectado;

      if (status === 'CONECTADA') {
        setStatus('diag-conexao', 'ok', `Conectado como <strong>${numero || 'seu número'}</strong>. Tudo certo!`);
      } else if (status === 'QRCODE') {
        setStatus('diag-conexao', 'atencao', 'Aguardando leitura do QR code — finalize a conexão no <a href="/whatsapp/kanban">Funil de Conversas</a>.');
      } else {
        setStatus('diag-conexao', 'erro', 'WhatsApp não conectado. Vá ao <a href="/whatsapp/kanban">Funil de Conversas</a> → botão <strong>Conectar</strong> e escaneie o QR.');
      }
    } catch (e) {
      setStatus('diag-conexao', 'erro', 'Não foi possível consultar o status — verifique sua internet e recarregue a página.');
    }
  }

  function testarRealtime() {
    setStatus('diag-realtime', 'testando', 'Verificando WebSocket...');

    // Dá alguns segundos para o Echo terminar o handshake após o load
    setTimeout(() => {
      if (typeof window.Echo === 'undefined') {
        setStatus('diag-realtime', 'erro', 'Módulo de tempo real não carregou — recarregue a página (Ctrl+F5). Persistindo, avise o suporte.');
        return;
      }

      const estadoWs = window.Echo.connector?.pusher?.connection?.state;

      if (estadoWs === 'connected') {
        setStatus('diag-realtime', 'ok', 'WebSocket conectado — mensagens chegam instantaneamente.');
      } else if (estadoWs === 'connecting' || estadoWs === 'initialized') {
        setStatus('diag-realtime', 'atencao', `Ainda conectando (${estadoWs}). Aguarde alguns segundos e clique em "Testar agora" de novo.`);
      } else {
        setStatus('diag-realtime', 'erro', `WebSocket ${estadoWs || 'indisponível'} — as mensagens só aparecem ao atualizar a página. Redes com proxy/VPN costumam bloquear; teste em outra rede e avise o suporte.`);
      }
    }, 1500);
  }

  function testarSom() {
    const ativado = localStorage.getItem('wa-som') !== 'off';

    if (ativado) {
      setStatus('diag-som', 'ok', 'Som ativado. Lembre-se: navegadores só tocam áudio após o primeiro clique na página.');
    } else {
      setStatus('diag-som', 'atencao', 'Som silenciado por você — reative no alto-falante 🔊 no topo da lista de conversas.');
    }
  }

  async function testarMicrofone() {
    setStatus('diag-microfone', 'testando', 'Verificando permissão...');

    if (!navigator.mediaDevices || !window.MediaRecorder) {
      setStatus('diag-microfone', 'erro', 'Este navegador não suporta gravação de áudio — use Chrome ou Edge atualizados.');
      return;
    }

    try {
      const permissao = await navigator.permissions.query({ name: 'microphone' });

      if (permissao.state === 'granted') {
        setStatus('diag-microfone', 'ok', 'Permissão concedida — os áudios de voz vão funcionar.');
      } else if (permissao.state === 'prompt') {
        setStatus('diag-microfone', 'atencao', 'O navegador vai pedir permissão na primeira gravação — clique em <strong>Permitir</strong> quando aparecer.');
      } else {
        setStatus('diag-microfone', 'erro', 'Permissão negada. Clique no cadeado ao lado do endereço do site → Microfone → <strong>Permitir</strong>, e recarregue.');
      }
    } catch (e) {
      // Alguns navegadores não expõem a query de permissão — não é um erro real
      setStatus('diag-microfone', 'atencao', 'Não foi possível pré-verificar — o navegador vai pedir permissão na primeira gravação.');
    }
  }

  function rodarDiagnostico() {
    testarConexao();
    testarRealtime();
    testarSom();
    testarMicrofone();
  }

  btnRodar?.addEventListener('click', rodarDiagnostico);

  // Roda automaticamente ao abrir a página
  rodarDiagnostico();
})();
