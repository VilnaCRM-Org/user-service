import { Theme, createTheme } from '@mui/material';

import colorTheme from '@/components/UiColorTheme';
import { golos } from '@/config/Fonts';

export const theme: Theme = createTheme({
  components: {
    MuiButton: {
      styleOverrides: {
        root: {
          alignSelf: 'center',
          fontWeight: '600',
          fontSize: '1.125rem',
          padding: '1.25rem 2rem',
          color: colorTheme.palette.white.main,
          textTransform: 'none',
          textDecoration: 'none',
          fontFamily: golos.style.fontFamily,
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
