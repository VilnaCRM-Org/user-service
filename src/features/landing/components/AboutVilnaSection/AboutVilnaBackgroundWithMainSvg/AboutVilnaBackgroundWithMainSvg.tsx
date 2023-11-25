import { Box } from '@mui/material';
import React from 'react';

interface IBackgroundProps {
  children?: React.ReactNode;
  style?: React.CSSProperties;
}

export default function AboutVilnaBackgroundWithMainSvg({ children, style }: IBackgroundProps) {
  const boxStyle = {
    backgroundImage: `url("/assets/svg/about-vilna/background_main.svg")`,
    backgroundRepeat: 'no-repeat',
    backgroundSize: 'cover',
    height: '100%',
    width: '100%',
    maxWidth: '798px',
    position: 'absolute',
    bottom: 0,
  };

  return <Box style={{ ...boxStyle, position: 'absolute', ...style }}>{children}</Box>;
}

AboutVilnaBackgroundWithMainSvg.defaultProps = {
  children: null,
  style: {},
};
