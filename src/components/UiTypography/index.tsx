import { ThemeProvider, Typography } from '@mui/material';
import React from 'react';

import { UiTypographyProps } from '@/components/UiTypography/types';

import theme from './theme';

function UiTypography({
  sx,
  children,
  component,
  variant,
  id,
  role,
}: UiTypographyProps): React.ReactElement {
  return (
    <ThemeProvider theme={theme}>
      <Typography sx={sx} component={component || 'p'} variant={variant} id={id} role={role}>
        {children}
      </Typography>
    </ThemeProvider>
  );
}

export default UiTypography;
