/* eslint-disable react/jsx-props-no-spreading */

import { Toolbar, ThemeProvider, ToolbarProps } from '@mui/material';

import { theme } from './theme';

function UiToolbar(props: ToolbarProps) {
  const { children } = props;
  return (
    <ThemeProvider theme={theme}>
      <Toolbar {...props}>{children}</Toolbar>
    </ThemeProvider>
  );
}

export default UiToolbar;
