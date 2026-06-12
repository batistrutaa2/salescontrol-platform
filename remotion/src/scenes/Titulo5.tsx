import React from 'react';
import {AbsoluteFill, interpolate, spring, useCurrentFrame, useVideoConfig} from 'remotion';
import {CORES, FONTES, OURO_GRADIENTE} from '../theme';

const CAMADAS_EXTRUSAO = 26;

/**
 * O "5" monumental em 3D real: pilha de camadas transladadas em Z
 * (extrusão), girando lentamente em Y dentro de um palco com perspectiva.
 */
export const Titulo5: React.FC = () => {
  const frame = useCurrentFrame();
  const {fps, durationInFrames} = useVideoConfig();

  const chegada = spring({frame, fps, config: {damping: 13, stiffness: 60, mass: 1.2}, durationInFrames: 55});
  const giro = interpolate(frame, [0, durationInFrames], [-34, 14]);
  const anos = interpolate(frame, [35, 60], [0, 1], {extrapolateLeft: 'clamp', extrapolateRight: 'clamp'});
  const periodo = interpolate(frame, [50, 75], [0, 1], {extrapolateLeft: 'clamp', extrapolateRight: 'clamp'});
  const saida = interpolate(frame, [durationInFrames - 22, durationInFrames - 4], [1, 0], {
    extrapolateLeft: 'clamp',
    extrapolateRight: 'clamp',
  });
  const brilho = interpolate(Math.sin(frame * 0.08), [-1, 1], [0.85, 1.15]);

  return (
    <AbsoluteFill style={{justifyContent: 'center', alignItems: 'center', opacity: saida}}>
      <div style={{perspective: 1400, transform: `scale(${interpolate(chegada, [0, 1], [0.55, 1])})`, opacity: chegada}}>
        <div
          style={{
            transformStyle: 'preserve-3d',
            transform: `rotateY(${giro}deg) rotateX(8deg)`,
            position: 'relative',
            width: 560,
            height: 640,
          }}
        >
          {Array.from({length: CAMADAS_EXTRUSAO}, (_, i) => {
            const profundidade = CAMADAS_EXTRUSAO - 1 - i;
            const ehFace = profundidade === 0;
            return (
              <div
                key={i}
                style={{
                  position: 'absolute',
                  inset: 0,
                  display: 'flex',
                  justifyContent: 'center',
                  alignItems: 'center',
                  transform: `translateZ(${-profundidade * 3.2}px)`,
                  fontFamily: FONTES.display,
                  fontWeight: 700,
                  fontSize: 620,
                  lineHeight: 1,
                  ...(ehFace
                    ? {
                        background: OURO_GRADIENTE,
                        WebkitBackgroundClip: 'text',
                        WebkitTextFillColor: 'transparent',
                        filter: `drop-shadow(0 0 ${55 * brilho}px rgba(212, 175, 55, 0.45))`,
                      }
                    : {
                        color: `rgba(${profundidade > CAMADAS_EXTRUSAO * 0.6 ? '26, 16, 51' : '94, 62, 16'}, ${0.92 - profundidade * 0.012})`,
                      }),
                }}
              >
                5
              </div>
            );
          })}
        </div>
      </div>

      <div style={{position: 'absolute', bottom: 200, textAlign: 'center'}}>
        <div
          style={{
            fontFamily: FONTES.corpo,
            fontWeight: 700,
            fontSize: 64,
            letterSpacing: '0.78em',
            paddingLeft: '0.78em',
            textTransform: 'uppercase',
            color: CORES.marfim,
            opacity: anos,
            transform: `translateY(${interpolate(anos, [0, 1], [30, 0])}px)`,
          }}
        >
          Anos
        </div>
        <div
          style={{
            marginTop: 18,
            fontFamily: FONTES.mono,
            fontSize: 26,
            letterSpacing: '0.3em',
            color: CORES.ouro,
            opacity: periodo,
          }}
        >
          2021 — 2026
        </div>
      </div>
    </AbsoluteFill>
  );
};
