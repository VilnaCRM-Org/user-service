import { createTheme, InputLabel, ThemeProvider } from '@mui/material';
import React from 'react';

import { UiTypography } from '../UiTypography';

import { labelProps } from './UiLabelType';

const theme = createTheme({});

function UiLabel({ children, sx, title }: labelProps) {
  return (
    <ThemeProvider theme={theme}>
      <UiTypography variant="medium14" sx={sx}>
        {title}
      </UiTypography>
      <InputLabel>{children}</InputLabel>
    </ThemeProvider>
  );
}
export default UiLabel;
