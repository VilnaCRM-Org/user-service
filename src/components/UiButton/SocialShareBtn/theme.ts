import { Theme, createTheme } from '@mui/material';

import breakpointsTheme from '@/components/UiBreakpoints';
import colorTheme from '@/components/UiColorTheme';
import { golos } from '@/config/Fonts';

export const theme: Theme = createTheme({
  components: {
    MuiButton: {
      styleOverrides: {
        root: {
          fontFamily: golos.style.fontFamily,
          textTransform: 'none',
          borderRadius: '0.75rem',
          width: '11.813rem',
          padding: '1.063rem',
          gap: '0.563rem',
          border: `1px solid ${colorTheme.palette.brandGray.main}`,
          background: colorTheme.palette.white.main,
          color: colorTheme.palette.grey200.main,
          [`@media (max-width: ${breakpointsTheme.breakpoints.values.sm}px)`]: {
            width: '100%',
            maxWidth: '10.563rem',
          },
          '&:hover': {
            background: colorTheme.palette.white.main,
            boxShadow: '0px 4px 7px 0px rgba(116, 134, 151, 0.17)',
            border: `1px solid ${colorTheme.palette.brandGray.main}`,
          },
          '&:active': {
            background: colorTheme.palette.white.main,
            boxShadow: '0px 4px 7px 0px rgba(71, 85, 99, 0.21)',
            border: `1px solid ${colorTheme.palette.grey300.main}`,
          },
          '&:disabled': {
            background: colorTheme.palette.brandGray.main,
            boxShadoiw: 'none',
            border: 'none',
            img: {
              opacity: '0.2',
            },
            div: {
              color: colorTheme.palette.white.main,
            },
          },
        },
      },
    },
  },
});
