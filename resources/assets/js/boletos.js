document.addEventListener('DOMContentLoaded', () => {
  const modal = document.getElementById('boleto-config');
  const form = document.getElementById('boleto-config-form');
  const dia = document.getElementById('boleto-dia');
  const proximo = document.getElementById('boleto-proximo');
  const restore = document.getElementById('boleto-restaurar');
  const atualizarAlerta = () => {
    const campo = document.getElementById('boleto-notificacao');
    campo.value = '';
    if (!proximo.value) return;
    const data = new Date(`${proximo.value}T12:00:00Z`);
    if (Number.isNaN(data.getTime())) return;
    data.setUTCDate(data.getUTCDate() - 10);
    campo.value = data.toISOString().slice(0, 10);
  };
  const populate = source => {
    form.action = source.dataset.url;
    form.querySelector('[name="_method"]').value = source.dataset.metodo || 'PUT';
    const manual = source.dataset.manual === '1';
    document.getElementById('boleto-manual-fields').hidden = !manual;
    document.getElementById('boleto-config-cliente').hidden = manual;
    const nome = document.getElementById('boleto-nome');
    const referencia = document.getElementById('boleto-referencia');
    nome.disabled = referencia.disabled = !manual;
    nome.required = manual;
    nome.value = source.dataset.nome || '';
    referencia.value = source.dataset.referencia || '';
    document.getElementById('boleto-venda').value = source.dataset.venda || '';
    document.getElementById('boleto-config-cliente').textContent = source.dataset.nome || '';
    dia.value = source.dataset.dia || '';
    proximo.value = source.dataset.proximo || '';
    atualizarAlerta();
    document.getElementById('boleto-ativo').checked = source.dataset.ativo === '1';
    const errors = document.getElementById('boleto-config-erros');
    if (errors) errors.hidden = source !== restore;
  };
  modal?.addEventListener('show.bs.modal', event => {
    const button = event.relatedTarget;
    if (button) {
      const same = restore && restore.dataset.manual === button.dataset.manual
        && (button.dataset.manual === '1' ? (restore.dataset.agenda || '') === (button.dataset.agenda || '') : restore.dataset.venda === button.dataset.venda);
      populate(same ? restore : button);
    }
  });
  if (restore) {
    populate(restore);
    bootstrap.Modal.getOrCreateInstance(modal).show();
  }
  proximo?.addEventListener('change', atualizarAlerta);
  dia?.addEventListener('change', () => {
    const day = Number(dia.value);
    if (!Number.isInteger(day) || day < 1 || day > 31) return;
    const [year, month, today] = proximo.dataset.hoje.split('-').map(Number);
    let target = new Date(year, month - 1, Math.min(day, new Date(year, month, 0).getDate()), 12);
    if (target.getDate() < today) target = new Date(year, month, Math.min(day, new Date(year, month + 1, 0).getDate()), 12);
    proximo.value = `${target.getFullYear()}-${String(target.getMonth() + 1).padStart(2, '0')}-${String(target.getDate()).padStart(2, '0')}`;
    proximo.dispatchEvent(new Event('change'));
  });
  document.querySelectorAll('.boletos form, #boleto-config-form').forEach(item => {
    item.addEventListener('submit', event => {
      if (item.dataset.confirm && !window.confirm(item.dataset.confirm)) {
        event.preventDefault();
        return;
      }
      if (!item.checkValidity()) return;
      const button = item.querySelector('button[type="submit"]');
      if (button) button.disabled = true;
    });
  });
});
