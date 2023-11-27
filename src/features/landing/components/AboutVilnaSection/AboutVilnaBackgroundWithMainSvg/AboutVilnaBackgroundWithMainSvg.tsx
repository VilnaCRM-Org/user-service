import { Box } from '@mui/material';
import React from 'react';

interface IBackgroundProps {
  children?: React.ReactNode;
  style?: React.CSSProperties;
}

const styles = {
  mainBox: {
    backgroundImage: `url("/assets/svg/about-vilna/background_main.svg")`,
    backgroundRepeat: 'no-repeat',
    backgroundSize: 'cover',
    height: '100%',
    width: '100%',
    maxWidth: '49.875rem', // 798px
    position: 'absolute',
    bottom: 0,
  },
};

export default function AboutVilnaBackgroundWithMainSvg({ children, style }: IBackgroundProps) {
  return <Box style={{ ...styles.mainBox, position: 'absolute', ...style }}>{children}</Box>;
}

AboutVilnaBackgroundWithMainSvg.defaultProps = {
  children: null,
  style: {},
};
