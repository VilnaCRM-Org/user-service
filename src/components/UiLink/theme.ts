import { createTheme } from '@mui/material';

import { colorTheme } from '../UiColorTheme';

export const theme = createTheme({
  components: {
    MuiLink: {
      styleOverrides: {
        root: {
          color: colorTheme.palette.primary.main,
          fontFamily: 'Inter',
          fontSize: '0.875rem',
          fontStyle: 'normal',
          fontWeight: '700',
          lineHeight: '1.125rem',
          textDecoration: 'underline',
          '@media (max-width: 1439.98px)': {
            fontSize: '1rem',
          },
          '@media (max-width: 639.98px)': {
            fontSize: '0.875rem',
          },
          '&:hover': {
            color: '#297FFF',
          },
        },
      },
    },
  },
});
