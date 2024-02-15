import { Theme, createTheme } from '@mui/material';

import colorTheme from '@/components/UiColorTheme';
import { golos } from '@/config/Fonts';

export const theme: Theme = createTheme({
  components: {
    MuiButton: {
      styleOverrides: {
        root: {
          color: colorTheme.palette.white.main,
          padding: '1rem 1.5rem',
          textTransform: 'none',
          textDecoration: 'none',
          fontSize: '0.938rem',
          fontFamily: golos.style.fontFamily,
          fontWeight: '500',
          lineHeight: '1.125',
          letterSpacing: '0',
          backgroundColor: colorTheme.palette.primary.main,
          borderRadius: '3.563rem',
          '&:hover': {
            backgroundColor: colorTheme.palette.containedButtonHover.main,
          },
          '&:active': {
            backgroundColor: colorTheme.palette.containedButtonActive.main,
          },
          '&:disabled': {
            backgroundColor: colorTheme.palette.brandGray.main,
            color: colorTheme.palette.white.main,
          },
        },
      },
    },
  },
});
