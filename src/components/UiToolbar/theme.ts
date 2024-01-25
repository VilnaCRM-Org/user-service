import { createTheme } from '@mui/material';

export const theme = createTheme({
  components: {
    MuiToolbar: {
      styleOverrides: {
        root: {
          padding: 0,
          margin: 0,
          justifyContent: 'space-between',
          '@media (min-width: 23.438rem)': {
            padding: '0 32px',
            width: '100%',
            margin: '0 auto',
            maxWidth: '78.375rem',
          },
          '@media (max-width: 425px)': {
            padding: '0 15px',
          },
        },
      },
    },
  },
});
