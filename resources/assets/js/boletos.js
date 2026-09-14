document.addEventListener('DOMContentLoaded', () => {
  const modal = document.getElementById('boleto-config');
  const form = document.getElementById('boleto-config-form');
  const dia = document.getElementById('boleto-dia');
  const proximo = document.getElementById('boleto-proximo');
  const restore = document.getElementById('boleto-restaurar');
  const populate = source => {
    form.action = source.dataset.url;
    document.getElementById('boleto-venda').value = source.dataset.venda;
    document.getElementById('boleto-config-cliente').textContent = source.dataset.nome;
    dia.value = source.dataset.dia || '';
    proximo.value = source.dataset.proximo || '';
    document.getElementById('boleto-ativo').checked = source.dataset.ativo === '1';
    const errors = document.getElementById('boleto-config-erros');
    if (errors) errors.hidden = source !== restore;
  };
  modal?.addEventListener('show.bs.modal', event => {
    const button = event.relatedTarget;
    if (button) populate(restore?.dataset.venda === button.dataset.venda ? restore : button);
  });
  if (restore) {
    populate(restore);
    bootstrap.Modal.getOrCreateInstance(modal).show();
  }
  dia?.addEventListener('change', () => {
    const day = Number(dia.value);
    if (!Number.isInteger(day) || day < 1 || day > 31) return;
    const [year, month, today] = proximo.dataset.hoje.split('-').map(Number);
    let target = new Date(year, month - 1, Math.min(day, new Date(year, month, 0).getDate()), 12);
    if (target.getDate() < today) target = new Date(year, month, Math.min(day, new Date(year, month + 1, 0).getDate()), 12);
    proximo.value = `${target.getFullYear()}-${String(target.getMonth() + 1).padStart(2, '0')}-${String(target.getDate()).padStart(2, '0')}`;
  });
  document.querySelectorAll('.boletos form, #boleto-config-form').forEach(item => {
    item.addEventListener('submit', () => {
      if (!item.checkValidity()) return;
      const button = item.querySelector('button[type="submit"]');
      if (button) button.disabled = true;
    });
  });
});
