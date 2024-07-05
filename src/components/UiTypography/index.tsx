import { ThemeProvider, Typography, TypographyProps } from '@mui/material';
import React from 'react';

import theme from './theme';

function UiTypography({
  sx,
  children,
  component,
  variant,
  id,
  role,
}: TypographyProps): React.ReactElement {
  return (
    <ThemeProvider theme={theme}>
      <Typography sx={sx} component={component || 'p'} variant={variant} id={id} role={role}>
        {children}
      </Typography>
    </ThemeProvider>
  );
}

export default UiTypography;
