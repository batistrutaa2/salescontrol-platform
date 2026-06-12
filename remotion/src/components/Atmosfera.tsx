import React from 'react';
import {AbsoluteFill, useCurrentFrame} from 'remotion';
import {CORES} from '../theme';

/** Fundo atmosférico: gradiente midnight + auras violeta/ouro que respiram + vinheta */
export const Atmosfera: React.FC = () => {
  const frame = useCurrentFrame();
  const respira = Math.sin(frame * 0.025);

  return (
    <AbsoluteFill>
      <AbsoluteFill
        style={{
          background: `radial-gradient(ellipse 120% 90% at 50% 110%, ${CORES.fundo} 0%, ${CORES.fundoProfundo} 70%)`,
        }}
      />
      <AbsoluteFill
        style={{
          background: `radial-gradient(circle 700px at ${22 + respira * 4}% 30%, rgba(124, 58, 237, 0.22), transparent 70%)`,
        }}
      />
      <AbsoluteFill
        style={{
          background: `radial-gradient(circle 600px at ${78 - respira * 4}% 72%, rgba(212, 175, 55, 0.13), transparent 70%)`,
        }}
      />
      {/* vinheta */}
      <AbsoluteFill
        style={{
          background: 'radial-gradient(ellipse 90% 80% at 50% 50%, transparent 55%, rgba(4, 2, 12, 0.75) 100%)',
        }}
      />
    </AbsoluteFill>
  );
};
