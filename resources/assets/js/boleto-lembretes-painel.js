const aviso = document.getElementById('boleto-painel-aviso');
let updating = false;
async function atualizarLembretes() {
  if (!aviso || updating || document.hidden) return;
  updating = true;
  try {
    const response = await fetch(aviso.dataset.resumoUrl, {headers: {Accept: 'application/json'}});
    if (!response.ok) return;
    const data = await response.json();
    const text = `${data.total} lembrete(s) aguardando acompanhamento${data.hoje ? `; ${data.hoje} com notificação hoje` : ''}.`;
    const target = document.getElementById('boleto-painel-texto');
    if (target.textContent !== text) target.textContent = text;
    aviso.classList.toggle('d-none', data.total === 0);
  } catch {
    // Preserve the last server-rendered reminder during a temporary connection failure.
  } finally {
    updating = false;
  }
}
if (aviso) {
  setInterval(atualizarLembretes, 60000);
  document.addEventListener('visibilitychange', atualizarLembretes);
}
