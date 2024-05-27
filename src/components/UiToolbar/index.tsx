import { Toolbar, ThemeProvider } from '@mui/material';

import { theme } from './theme';

function UiToolbar({ children }: { children: React.ReactNode }): React.ReactElement {
  return (
    <ThemeProvider theme={theme}>
      <Toolbar>{children}</Toolbar>
    </ThemeProvider>
  );
}

export default UiToolbar;
