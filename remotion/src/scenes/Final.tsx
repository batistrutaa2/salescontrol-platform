import React, {useMemo} from 'react';
import {AbsoluteFill, interpolate, random, spring, useCurrentFrame, useVideoConfig} from 'remotion';
import {ASSINATURA, FRASE_FINAL} from '../timeline';
import {CORES, FONTES, OURO_GRADIENTE} from '../theme';

const CONFETES = 110;

/** Fecho: explosão contida de confete dourado/violeta + frase final + assinatura */
export const Final: React.FC = () => {
  const frame = useCurrentFrame();
  const {fps, width, height, durationInFrames} = useVideoConfig();

  const frase = spring({frame: frame - 10, fps, config: {damping: 14, stiffness: 65}, durationInFrames: 50});
  const assina = interpolate(frame, [55, 80], [0, 1], {extrapolateLeft: 'clamp', extrapolateRight: 'clamp'});
  const saida = interpolate(frame, [durationInFrames - 30, durationInFrames - 6], [1, 0], {
    extrapolateLeft: 'clamp',
    extrapolateRight: 'clamp',
  });

  const confetes = useMemo(
    () =>
      Array.from({length: CONFETES}, (_, i) => ({
        anguloRad: random(`fim-a-${i}`) * Math.PI * 2,
        forca: 380 + random(`fim-f-${i}`) * 620,
        tamanho: 7 + random(`fim-t-${i}`) * 13,
        dourado: random(`fim-c-${i}`) > 0.38,
        rotacao: random(`fim-r-${i}`) * 720 - 360,
        atraso: random(`fim-d-${i}`) * 8,
      })),
    []
  );

  return (
    <AbsoluteFill style={{justifyContent: 'center', alignItems: 'center', opacity: saida}}>
      {/* confete radial */}
      {confetes.map((c, i) => {
        const t = Math.max(0, frame - c.atraso);
        const progresso = interpolate(t, [0, 70], [0, 1], {extrapolateRight: 'clamp'});
        const suave = 1 - (1 - progresso) ** 3;
        const dist = c.forca * suave;
        const gravidade = 240 * progresso ** 2;
        const x = width / 2 + Math.cos(c.anguloRad) * dist;
        const y = height / 2 + Math.sin(c.anguloRad) * dist * 0.72 + gravidade;
        return (
          <div
            key={i}
            style={{
              position: 'absolute',
              left: x,
              top: y,
              width: c.tamanho,
              height: c.tamanho * 0.55,
              borderRadius: 2,
              background: c.dourado ? OURO_GRADIENTE : CORES.violeta,
              opacity: interpolate(progresso, [0, 0.1, 0.8, 1], [0, 1, 0.9, 0]),
              transform: `rotate(${c.rotacao * progresso}deg)`,
            }}
          />
        );
      })}

      <div style={{textAlign: 'center', maxWidth: 1300, padding: '0 60px'}}>
        <div
          style={{
            fontFamily: FONTES.display,
            fontWeight: 600,
            fontSize: 92,
            lineHeight: 1.18,
            whiteSpace: 'pre-line',
            color: CORES.marfim,
            opacity: frase,
            transform: `perspective(900px) translateZ(${interpolate(frase, [0, 1], [-300, 0])}px)`,
          }}
        >
          {FRASE_FINAL}
        </div>
        <div
          style={{
            marginTop: 44,
            fontFamily: FONTES.mono,
            fontSize: 27,
            letterSpacing: '0.32em',
            background: OURO_GRADIENTE,
            WebkitBackgroundClip: 'text',
            WebkitTextFillColor: 'transparent',
            opacity: assina,
          }}
        >
          {ASSINATURA}
        </div>
      </div>
    </AbsoluteFill>
  );
};
