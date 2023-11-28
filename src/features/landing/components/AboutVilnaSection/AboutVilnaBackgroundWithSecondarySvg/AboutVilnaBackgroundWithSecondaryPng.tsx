import { Box } from '@mui/material';
import React from 'react';

interface IBackgroundProps {
  children?: React.ReactNode;
  style?: React.CSSProperties;
}

const styles = {
  mainBox: {
    height: '100%',
    width: '100%',
    maxHeight: '17.75rem', // 284px
    maxWidth: '14.175rem', // 226.8px
    position: 'relative',
    bottom: 0,
  },
};

export default function AboutVilnaBackgroundWithSecondaryPng({ children, style }: IBackgroundProps) {
  return <Box style={{ ...styles.mainBox, position: 'relative', ...style }}>{children}</Box>;
}

AboutVilnaBackgroundWithSecondaryPng.defaultProps = {
  children: null,
  style: {},
};
