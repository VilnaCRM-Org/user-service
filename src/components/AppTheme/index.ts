import { Theme, createTheme } from '@mui/material';

import breakpointsTheme from '../UiBreakpoints';
import colorTheme from '../UiColorTheme';

export const theme: Theme = createTheme({
  breakpoints: breakpointsTheme.breakpoints,
  palette: colorTheme.palette,
  components: {
    MuiContainer: {
      styleOverrides: {
        root: {
          '@media (min-width: 23.438rem)': {
            padding: '0 2rem',
          },
          '@media (max-width: 26.563rem)': {
            padding: '0 0.9375rem',
          },
          '@media (min-width: 64rem)': {
            width: '100%',
            maxWidth: '78.375rem',
            paddingLeft: '2rem',
            paddingRight: '2rem',
          },
        },
      },
    },
  },
});
