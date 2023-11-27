import { Box } from '@mui/material';
import React from 'react';

interface IBackgroundProps {
  children?: React.ReactNode;
  style?: React.CSSProperties;
}

const styles = {
  mainBox: {
    backgroundImage: `url("/assets/svg/about-vilna/background_secondary.svg")`,
    backgroundRepeat: 'no-repeat',
    backgroundSize: 'cover',
    height: '100%',
    width: '100%',
    maxHeight: '17.75rem', // 284px
    maxWidth: '14.175rem', // 226.8px
    position: 'absolute',
    bottom: 0,
  },
};

export default function AboutVilnaBackgroundWithSecondarySvg({ children, style }: IBackgroundProps) {
  return <Box style={{ ...styles.mainBox, position: 'absolute', ...style }}>{children}</Box>;
}

AboutVilnaBackgroundWithSecondarySvg.defaultProps = {
  children: null,
  style: {},
};
