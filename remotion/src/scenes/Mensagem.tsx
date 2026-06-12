import React from 'react';
import {AbsoluteFill, interpolate, spring, useCurrentFrame, useVideoConfig} from 'remotion';
import type {Mensagem as MensagemData} from '../timeline';
import {CORES, FONTES, OURO_GRADIENTE} from '../theme';

/** Cena de mensagem: título serif flutuando do fundo, filete dourado e texto de apoio */
export const Mensagem: React.FC<{mensagem: MensagemData}> = ({mensagem}) => {
  const frame = useCurrentFrame();
  const {fps, durationInFrames} = useVideoConfig();

  const titulo = spring({frame, fps, config: {damping: 14, stiffness: 70}, durationInFrames: 45});
  const filete = interpolate(frame, [20, 45], [0, 180], {extrapolateLeft: 'clamp', extrapolateRight: 'clamp'});
  const texto = interpolate(frame, [32, 58], [0, 1], {extrapolateLeft: 'clamp', extrapolateRight: 'clamp'});
  const saida = interpolate(frame, [durationInFrames - 20, durationInFrames - 4], [1, 0], {
    extrapolateLeft: 'clamp',
    extrapolateRight: 'clamp',
  });

  return (
    <AbsoluteFill style={{justifyContent: 'center', alignItems: 'center', opacity: saida}}>
      <div style={{textAlign: 'center', maxWidth: 1400, padding: '0 80px'}}>
        <div
          style={{
            fontFamily: FONTES.display,
            fontWeight: 600,
            fontSize: 88,
            lineHeight: 1.2,
            whiteSpace: 'pre-line',
            color: CORES.marfim,
            opacity: titulo,
            transform: `perspective(900px) translateZ(${interpolate(titulo, [0, 1], [-280, 0])}px)`,
          }}
        >
          {mensagem.titulo}
        </div>
        <div
          style={{
            height: 2,
            width: filete,
            margin: '34px auto',
            background: OURO_GRADIENTE,
            boxShadow: '0 0 18px rgba(212, 175, 55, 0.5)',
          }}
        />
        <div
          style={{
            fontFamily: FONTES.corpo,
            fontWeight: 400,
            fontSize: 36,
            lineHeight: 1.5,
            color: CORES.textoSuave,
            opacity: texto,
            transform: `translateY(${interpolate(texto, [0, 1], [22, 0])}px)`,
          }}
        >
          {mensagem.texto}
        </div>
      </div>
    </AbsoluteFill>
  );
};
