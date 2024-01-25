import { createTheme } from '@mui/material';

import { colorTheme } from '../UiColorTheme';

export const theme = createTheme({
  components: {
    MuiLink: {
      styleOverrides: {
        root: {
          color: colorTheme.palette.primary.main,
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
          '&:hover': {
            color: '#297FFF',
          },
        },
      },
    },
  },
});
