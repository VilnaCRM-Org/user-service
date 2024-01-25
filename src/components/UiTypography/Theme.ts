import { createTheme } from '@mui/material';

import { colorTheme } from '../UiColorTheme';

const fonts = {
  golos: 'Golos',
  inter: 'Inter',
};

const hStyles = {
  color: colorTheme.palette.darkPrimary.main,
  fontFamily: fonts.golos,
  fontWeight: '700',
  lineHeight: 'normal',
  letterSpacing: '',
};

const theme = createTheme({
  components: {
    MuiTypography: {
      defaultProps: {
        variantMapping: {
          medium16: 'p',
          medium15: 'p',
          medium14: 'p',
          regular16: 'p',
          bodyText18: 'p',
          bodyText16: 'p',
          bold22: 'p',
          demi18: 'p',
          button: 'p',
          mobileText: 'p',
        },
      },
    },
  },
  typography: {
    h1: {
      ...hStyles,
      fontSize: '3.5rem',
    },
    h2: {
      ...hStyles,
      fontSize: '2.875rem',
    },
    h3: {
      ...hStyles,
      fontSize: '2.25rem',
      fontWeight: '600',
    },
    h4: {
      ...hStyles,
      color: '#484848',
      fontSize: '1.875rem',
      fontWeight: '600',
    },
    h5: {
      ...hStyles,
      fontSize: '1.75rem',
    },
    h6: {
      ...hStyles,
      fontSize: '1.375rem',
    },
    medium16: {
      font: `normal 500 1rem/1.125rem ${fonts.inter}`,
      color: colorTheme.palette.grey300.main,
    },
    medium15: {
      font: `normal 500 0.9375rem/1.125rem ${fonts.golos}`,
      color: colorTheme.palette.grey250.main,
    },
    medium14: {
      font: `normal 500 0.875rem/1.125rem ${fonts.inter}`,
      color: colorTheme.palette.grey200.main,
    },
    regular16: {
      font: `normal 500 1rem/1.125rem ${fonts.inter}`,
      color: colorTheme.palette.grey300.main,
    },
    bodyText18: {
      font: `normal 400 1.125rem/1.875rem ${fonts.golos}`,
      color: colorTheme.palette.darkPrimary.main,
    },
    bodyText16: {
      font: `normal 400 1rem/1.625rem ${fonts.golos}`,
      color: colorTheme.palette.darkPrimary.main,
    },
    bold22: {
      font: `normal 700 1.375rem/normal ${fonts.golos}`,
      color: colorTheme.palette.grey250.main,
    },
    demi18: {
      font: `normal 600 1.125rem/normal ${fonts.golos}`,
      color: colorTheme.palette.darkPrimary.main,
    },
    button: {
      font: `normal 600 1.125rem/normal ${fonts.golos}`,
      color: colorTheme.palette.white.main,
    },
    mobileText: {
      font: `normal 400 0.9375rem/1.563rem ${fonts.golos}`,
      color: colorTheme.palette.darkPrimary.main,
    },
  },
});

export default theme;
