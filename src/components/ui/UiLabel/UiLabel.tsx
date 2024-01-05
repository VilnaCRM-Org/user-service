import { createTheme, InputLabel, ThemeProvider } from '@mui/material';
import React from 'react';

import { UiTypography } from '../UiTypography';

import { labelProps } from './UiLabelType';

const theme = createTheme({});

function UiLabel({ children, sx, errorText, hasError, title }: labelProps) {
  return (
    <ThemeProvider theme={theme}>
      <UiTypography variant="medium14" sx={sx}>
        {title}
      </UiTypography>
      <InputLabel>{children}</InputLabel>
      {hasError ? (
        <UiTypography sx={{ color: 'red', pt: '4px' }} variant="medium14">
          {errorText}
        </UiTypography>
      ) : null}
    </ThemeProvider>
  );
}
export default UiLabel;
