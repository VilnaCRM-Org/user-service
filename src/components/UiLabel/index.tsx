import { createTheme, InputLabel, ThemeProvider } from '@mui/material';
import React from 'react';

import UiTypography from '../UiTypography';

import { styles } from './styles';
import { labelProps } from './types';

const theme = createTheme({});

function UiLabel({ hasError, children, sx, title }: labelProps) {
  return (
    <ThemeProvider theme={theme}>
      {hasError ? (
        <UiTypography variant="medium14" sx={styles.title}>
          {title}
        </UiTypography>
      ) : (
        <UiTypography variant="medium14" sx={sx}>
          {title}
        </UiTypography>
      )}

      <InputLabel>{children}</InputLabel>
    </ThemeProvider>
  );
}
export default UiLabel;
