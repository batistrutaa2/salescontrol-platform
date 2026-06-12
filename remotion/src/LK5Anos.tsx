import React from 'react';
import {AbsoluteFill, Series} from 'remotion';
import {Atmosfera} from './components/Atmosfera';
import {Particles} from './components/Particles';
import {Intro} from './scenes/Intro';
import {Titulo5} from './scenes/Titulo5';
import {Mensagem} from './scenes/Mensagem';
import {Final} from './scenes/Final';
import {MENSAGENS} from './timeline';

export const FPS = 30;

const DUR_INTRO = 120;
const DUR_TITULO = 160;
const DUR_MENSAGEM = 160;
const DUR_FINAL = 210;

export const DURACAO_TOTAL = DUR_INTRO + DUR_TITULO + MENSAGENS.length * DUR_MENSAGEM + DUR_FINAL;

export const LK5Anos: React.FC = () => {
  return (
    <AbsoluteFill>
      <Atmosfera />
      <Particles quantidade={90} />
      <Series>
        <Series.Sequence durationInFrames={DUR_INTRO}>
          <Intro />
        </Series.Sequence>
        <Series.Sequence durationInFrames={DUR_TITULO}>
          <Titulo5 />
        </Series.Sequence>
        {MENSAGENS.map((mensagem, i) => (
          <Series.Sequence key={i} durationInFrames={DUR_MENSAGEM}>
            <Mensagem mensagem={mensagem} />
          </Series.Sequence>
        ))}
        <Series.Sequence durationInFrames={DUR_FINAL}>
          <Final />
        </Series.Sequence>
      </Series>
    </AbsoluteFill>
  );
};
