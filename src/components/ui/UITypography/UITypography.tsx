import { Typography, ThemeProvider, createTheme } from '@mui/material';
import React from 'react';

import { UITypographyProps } from './UITypographyTypes';

const defaultProps: UITypographyProps = {
  children: '',
};

const theme = createTheme({
  components: {
    MuiTypography: {
      defaultProps,
      variants: [],
    },
  },
});

function UITypography({ children }: UITypographyProps) {
  return (
    <ThemeProvider theme={theme}>
      <Typography>{children}</Typography>
    </ThemeProvider>
  );
}

export default UITypography;
