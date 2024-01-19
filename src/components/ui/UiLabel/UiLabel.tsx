import { createTheme, InputLabel, ThemeProvider } from '@mui/material';
import React from 'react';

import { UiTypography } from '../UiTypography';

import { labelProps } from './UiLabelType';

const theme = createTheme({});

function UiLabel({ hasError, children, sx, title }: labelProps) {
  return (
    <ThemeProvider theme={theme}>
      {hasError ? (
        <UiTypography
          variant="medium14"
          sx={{
            pb: '5px',
            mt: '6px',
            '@media (max-width: 639.98px)': { mt: '0px', pb: '0px' },
          }}
        >
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
