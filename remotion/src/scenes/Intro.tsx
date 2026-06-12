import React from 'react';
import {AbsoluteFill, interpolate, spring, useCurrentFrame, useVideoConfig} from 'remotion';
import {CORES, FONTES, OURO_GRADIENTE} from '../theme';

/** Abertura: monograma LK surge entre as partículas, "apresenta" em caixa alta espaçada */
export const Intro: React.FC = () => {
  const frame = useCurrentFrame();
  const {fps, durationInFrames} = useVideoConfig();

  const entrada = spring({frame, fps, config: {damping: 14, stiffness: 80}, durationInFrames: 45});
  const linhaW = interpolate(spring({frame: frame - 18, fps, config: {damping: 16}}), [0, 1], [0, 280]);
  const sub = interpolate(frame, [30, 55], [0, 1], {extrapolateLeft: 'clamp', extrapolateRight: 'clamp'});
  const saida = interpolate(frame, [durationInFrames - 24, durationInFrames - 4], [1, 0], {
    extrapolateLeft: 'clamp',
    extrapolateRight: 'clamp',
  });

  return (
    <AbsoluteFill style={{justifyContent: 'center', alignItems: 'center', opacity: saida}}>
      <div
        style={{
          transform: `perspective(1000px) translateZ(${interpolate(entrada, [0, 1], [-420, 0])}px) rotateX(${interpolate(entrada, [0, 1], [28, 0])}deg)`,
          opacity: entrada,
          textAlign: 'center',
        }}
      >
        <div
          style={{
            fontFamily: FONTES.display,
            fontWeight: 600,
            fontSize: 130,
            letterSpacing: '0.02em',
            background: OURO_GRADIENTE,
            WebkitBackgroundClip: 'text',
            WebkitTextFillColor: 'transparent',
            filter: 'drop-shadow(0 8px 40px rgba(212, 175, 55, 0.35))',
          }}
        >
          LK Brokers
        </div>
        <div
          style={{
            height: 1.5,
            width: linhaW,
            margin: '28px auto',
            background: `linear-gradient(90deg, transparent, ${CORES.ouro}, transparent)`,
          }}
        />
        <div
          style={{
            fontFamily: FONTES.corpo,
            fontWeight: 500,
            fontSize: 30,
            letterSpacing: '0.55em',
            textTransform: 'uppercase',
            color: CORES.textoSuave,
            opacity: sub,
            paddingLeft: '0.55em',
          }}
        >
          apresenta
        </div>
      </div>
    </AbsoluteFill>
  );
};
