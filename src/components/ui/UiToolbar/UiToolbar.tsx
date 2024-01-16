/* eslint-disable react/jsx-props-no-spreading */

import { Toolbar, ThemeProvider } from '@mui/material';

import { theme } from './theme';
import { UiToolbarProps } from './types';

function UiToolbar({ children, props }: UiToolbarProps) {
  return (
    <ThemeProvider theme={theme}>
      <Toolbar {...props}>{children}</Toolbar>
    </ThemeProvider>
  );
}

export default UiToolbar;
