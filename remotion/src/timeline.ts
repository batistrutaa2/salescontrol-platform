/**
 * ════════════════════════════════════════════════════════════════════
 *  5 ANOS LK BROKERS — ROTEIRO DO VÍDEO (EDITE AQUI)
 * ════════════════════════════════════════════════════════════════════
 *  Vídeo curto de 5 cenas: abertura → "5 anos" em 3D → duas mensagens
 *  de gratidão coletiva → fecho com confete. Sem números, resultados
 *  ou nomes — apenas o agradecimento a todos que constroem a LK.
 *  Para mudar os textos, edite abaixo e renderize de novo:
 *
 *      cd remotion && npm run render
 * ════════════════════════════════════════════════════════════════════
 */

export interface Mensagem {
  titulo: string;
  texto: string;
}

export const MENSAGENS: Mensagem[] = [
  {
    titulo: '5 anos não se constroem sozinhos.',
    texto: 'Essa história é feita de pessoas.',
  },
  {
    titulo: 'Obrigado a todos os corretores\ne colaboradores.',
    texto: 'Cada ligação, cada visita e cada contrato construíram esses 5 anos.',
  },
];

export const FRASE_FINAL = 'Vocês são a LK Brokers.\nObrigado por estes 5 anos.';
export const ASSINATURA = 'LK Brokers · 2021 — 2026';
