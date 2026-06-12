import {loadFont as loadFraunces} from '@remotion/google-fonts/Fraunces';
import {loadFont as loadJakarta} from '@remotion/google-fonts/PlusJakartaSans';
import {loadFont as loadMono} from '@remotion/google-fonts/JetBrainsMono';

const fraunces = loadFraunces();
const jakarta = loadJakarta();
const mono = loadMono();

export const FONTES = {
  display: fraunces.fontFamily,
  corpo: jakarta.fontFamily,
  mono: mono.fontFamily,
};

/** Paleta jubileu: midnight da marca + dourado celebratório */
export const CORES = {
  fundo: '#0B0817',
  fundoProfundo: '#060410',
  violeta: '#7C3AED',
  violetaClaro: '#A78BFA',
  ouro: '#D4AF37',
  ouroClaro: '#F6E27A',
  champagne: '#F3E5C3',
  marfim: '#FAF6EC',
  textoSuave: 'rgba(250, 246, 236, 0.62)',
};

export const OURO_GRADIENTE = `linear-gradient(135deg, ${CORES.ouroClaro} 0%, ${CORES.ouro} 38%, #9A7B1E 52%, ${CORES.ouroClaro} 68%, ${CORES.ouro} 100%)`;
