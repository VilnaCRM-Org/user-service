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
          '@media (max-width: 1439.98px)': {
            fontSize: '16px',
          },
          '@media (max-width: 639.98px)': {
            fontSize: '14px',
          },
        },
      },
    },
  },
});
