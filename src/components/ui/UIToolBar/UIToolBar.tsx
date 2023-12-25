import { Toolbar, ThemeProvider, createTheme } from '@mui/material';
import React from 'react';

const theme = createTheme({
  palette: {
    primary: {
      main: '#000000',
    },
  },
  components: {
    MuiToolbar: {
      styleOverrides: {
        root: {
          backgroundColor: 'white',
          padding: 0,
          margin: 0,
          justifyContent: 'space-between',
          '@media (min-width: 20.25rem)': {
            padding: 0,
            margin: 0,
          },
        },
      },
    },
  },
});

function CustomToolbar({ children }: { children: React.ReactNode }) {
  return (
    <ThemeProvider theme={theme}>
      <Toolbar>{children}</Toolbar>
    </ThemeProvider>
  );
}

export default CustomToolbar;
