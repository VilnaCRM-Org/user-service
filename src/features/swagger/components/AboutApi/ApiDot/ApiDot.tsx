import { Box } from '@mui/material';
import React from 'react';

import colorTheme from '@/components/UiColorTheme';

import styles from './styles';
import { ApiDotProps } from './types';

function ApiDot({ color }: ApiDotProps): React.ReactElement {
  const backgroundColor: string =
    color === 'black'
      ? colorTheme.palette.darkSecondary.main
      : colorTheme.palette.textLinkActive.main;

  return <Box sx={{ ...styles.dot, backgroundColor }} />;
}

export default ApiDot;
