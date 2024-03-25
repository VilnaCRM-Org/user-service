import { Theme, createTheme } from '@mui/material';

import { inter } from '@/config/Fonts/inter';

import breakpointsTheme from '../UiBreakpoints';
import colorTheme from '../UiColorTheme';

export const theme: Theme = createTheme({
  components: {
    MuiLink: {
      styleOverrides: {
        root: {
          color: colorTheme.palette.primary.main,
          fontFamily: inter.style.fontFamily,
          fontSize: '0.875rem',
          fontStyle: 'normal',
          fontWeight: '700',
          lineHeight: '1.125rem',
          textDecoration: 'underline',
          [`@media (max-width: 1130px)`]: {
            fontSize: '1rem',
          },
          [`@media (max-width: ${breakpointsTheme.breakpoints.values.sm}px)`]: {
            fontSize: '0.875rem',
          },
          '&:hover': {
            color: colorTheme.palette.textLinkHover.main,
          },
          '&:active': {
            color: colorTheme.palette.textLinkActive.main,
          },
        },
      },
    },
  },
});
