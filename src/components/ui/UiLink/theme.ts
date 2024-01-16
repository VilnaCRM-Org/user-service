import { createTheme } from '@mui/material';

export const theme = createTheme({
  components: {
    MuiLink: {
      styleOverrides: {
        root: {
          color: '#1EAEFF',
          fontFamily: 'Inter',
          fontSize: '14px',
          fontStyle: 'normal',
          fontWeight: '700',
          lineHeight: '18px',
          textDecoration: 'underline',
        },
      },
    },
  },
});
