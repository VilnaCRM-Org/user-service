import { Theme, createTheme } from '@mui/material';

import colorTheme from '@/components/UiColorTheme';
import { golos } from '@/config/Fonts';

export const theme: Theme = createTheme({
  components: {
    MuiButton: {
      styleOverrides: {
        root: {
          padding: '1rem 1.438rem',
          textTransform: 'none',
          textDecoration: 'none',
          fontSize: '0.938rem',
          fontFamily: golos.style.fontFamily,
          fontWeight: '500',
          lineHeight: '1.125',
          letterSpacing: '0',
          color: colorTheme.palette.darkSecondary.main,
          backgroundColor: colorTheme.palette.white.main,
          border: `1px solid ${colorTheme.palette.grey300.main}`,
          borderRadius: '3.563rem',
          '&:hover': {
            backgroundColor: colorTheme.palette.grey500.main,
            border: '1px solid rgba(0,0,0,0)',
          },
          '&:active': {
            border: `1px solid ${colorTheme.palette.grey500.main}`,
          },
          '&:disabled': {
            backgroundColor: colorTheme.palette.brandGray.main,
            color: colorTheme.palette.white.main,
            border: 'none',
          },
        },
      },
    },
  },
});
