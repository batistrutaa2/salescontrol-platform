import React, {useMemo} from 'react';
import {AbsoluteFill, random, useCurrentFrame, useVideoConfig, interpolate} from 'remotion';
import {CORES} from '../theme';

interface Particula {
  x: number;
  y: number;
  z: number;       // profundidade 0 (longe) → 1 (perto)
  tamanho: number;
  dourada: boolean;
  fase: number;
  velocidade: number;
}

/**
 * Campo de partículas em 3 camadas de profundidade (parallax via
 * translateZ). Posições determinísticas via random(seed) do Remotion.
 */
export const Particles: React.FC<{quantidade?: number; deriva?: number; seed?: string}> = ({
  quantidade = 90,
  deriva = 1,
  seed = 'lk',
}) => {
  const frame = useCurrentFrame();
  const {width, height} = useVideoConfig();

  const particulas = useMemo<Particula[]>(() => {
    return Array.from({length: quantidade}, (_, i) => ({
      x: random(`${seed}-x-${i}`) * width,
      y: random(`${seed}-y-${i}`) * height,
      z: random(`${seed}-z-${i}`),
      tamanho: 1.5 + random(`${seed}-s-${i}`) * 4,
      dourada: random(`${seed}-c-${i}`) > 0.45,
      fase: random(`${seed}-p-${i}`) * Math.PI * 2,
      velocidade: 0.15 + random(`${seed}-v-${i}`) * 0.45,
    }));
  }, [quantidade, width, height, seed]);

  return (
    <AbsoluteFill style={{perspective: 1200}}>
      {particulas.map((p, i) => {
        const subida = (frame * p.velocidade * deriva) % (height + 120);
        const y = ((p.y - subida) % (height + 120) + height + 120) % (height + 120) - 60;
        const balanco = Math.sin(frame * 0.02 + p.fase) * 18 * p.z;
        const cintila = interpolate(Math.sin(frame * 0.07 + p.fase * 3), [-1, 1], [0.25, 1]);
        const escala = 0.4 + p.z * 0.9;
        const cor = p.dourada ? CORES.ouroClaro : CORES.violetaClaro;
        return (
          <div
            key={i}
            style={{
              position: 'absolute',
              left: p.x + balanco,
              top: y,
              width: p.tamanho,
              height: p.tamanho,
              borderRadius: '50%',
              background: cor,
              opacity: cintila * (0.25 + p.z * 0.6),
              transform: `translateZ(${(p.z - 0.5) * 400}px) scale(${escala})`,
              boxShadow: `0 0 ${6 + p.z * 14}px ${cor}`,
            }}
          />
        );
      })}
    </AbsoluteFill>
  );
};
