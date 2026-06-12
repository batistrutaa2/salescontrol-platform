import {Composition} from 'remotion';
import {LK5Anos, DURACAO_TOTAL, FPS} from './LK5Anos';

export const Root: React.FC = () => {
  return (
    <Composition
      id="LK5Anos"
      component={LK5Anos}
      durationInFrames={DURACAO_TOTAL}
      fps={FPS}
      width={1920}
      height={1080}
    />
  );
};
