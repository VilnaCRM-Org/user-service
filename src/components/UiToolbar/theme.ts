import { createTheme, Theme } from '@mui/material';

export const theme: Theme = createTheme({
  components: {
    MuiToolbar: {
      styleOverrides: {
        root: {
          padding: 0,
          margin: 0,
          justifyContent: 'space-between',
          '@media (min-width: 425px)': {
            padding: '0 2rem',
            width: '100%',
            margin: '0 auto',
            maxWidth: '78.375rem',
          },
          '@media (max-width: 425px)': {
            padding: '0 0.9375rem',
          },
        },
      },
    },
  },
});
