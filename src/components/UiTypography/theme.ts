import { Theme, createTheme } from '@mui/material';
import { CSSProperties } from '@mui/material/styles/createMixins';

import { golos } from '@/config/Fonts/golos';
import { inter } from '@/config/Fonts/inter';

import colorTheme from '../UiColorTheme';

const hStyles: CSSProperties = {
  color: colorTheme.palette.darkPrimary.main,
  fontWeight: '700',
  lineHeight: 'normal',
  fontFamily: golos.style.fontFamily,
  letterSpacing: '',
};

const theme: Theme = createTheme({
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
      fontFamily: inter.style.fontFamily,
      fontWeight: '500',
      fontSize: '1rem',
      lineHeight: '1.125rem',
      color: colorTheme.palette.grey300.main,
    },
    medium15: {
      fontWeight: '500',
      fontSize: '0.9375rem',
      lineHeight: '1.125rem',
      color: colorTheme.palette.grey250.main,
    },
    medium14: {
      fontWeight: '500',
      fontSize: '0.875rem',
      lineHeight: '1.125rem',
      color: colorTheme.palette.grey200.main,
      fontFamily: inter.style.fontFamily,
    },
    regular16: {
      fontWeight: '500',
      fontSize: '1rem',
      lineHeight: '1.125rem',
      color: colorTheme.palette.grey300.main,
      fontFamily: golos.style.fontFamily,
    },
    bodyText18: {
      fontWeight: '400',
      fontSize: '1.125rem',
      lineHeight: '1.875rem',
      color: colorTheme.palette.darkPrimary.main,
      fontFamily: golos.style.fontFamily,
    },
    bodyText16: {
      fontWeight: '400',
      fontSize: '1rem',
      lineHeight: '1.625rem',
      color: colorTheme.palette.darkPrimary.main,
      fontFamily: golos.style.fontFamily,
    },
    bold22: {
      fontWeight: '700',
      fontSize: '1.375rem',
      lineHeight: 'normal',
      color: colorTheme.palette.grey250.main,
      fontFamily: golos.style.fontFamily,
    },
    demi18: {
      fontWeight: '600',
      fontSize: '1.125rem',
      lineHeight: 'normal',
      color: colorTheme.palette.darkPrimary.main,
      fontFamily: golos.style.fontFamily,
    },
    button: {
      fontWeight: '600',
      fontSize: '1.125rem',
      lineHeight: 'normal',
      color: colorTheme.palette.white.main,
      fontFamily: golos.style.fontFamily,
    },
    mobileText: {
      fontWeight: '400',
      fontSize: '0.9375rem',
      lineHeight: '1.563rem',
      color: colorTheme.palette.darkPrimary.main,
      fontFamily: golos.style.fontFamily,
    },
  },
});

export default theme;
