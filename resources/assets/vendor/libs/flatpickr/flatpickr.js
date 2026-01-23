import flatpickr from 'flatpickr/dist/flatpickr';
import { Portuguese } from 'flatpickr/dist/l10n/pt.js';

// Registrar locale português com nome 'pt' e definir como padrão
flatpickr.l10ns.pt = Portuguese;
flatpickr.localize(Portuguese);

try {
  window.flatpickr = flatpickr;
} catch (e) {}

export { flatpickr };
