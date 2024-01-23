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
      fontSize: '56px',
    },
    h2: {
      ...hStyles,
      fontSize: '46px',
    },
    h3: {
      ...hStyles,
      fontSize: '36px',
      fontWeight: '600',
    },
    h4: {
      ...hStyles,
      color: '#484848',
      fontSize: '30px',
      fontWeight: '600',
    },
    h5: {
      ...hStyles,
      fontSize: '28px',
    },
    h6: {
      ...hStyles,
      fontSize: '22px',
    },
    medium16: {
      font: `normal 500 16px/18px ${fonts.inter}`,
      color: colorTheme.palette.grey300.main,
    },
    medium15: {
      font: `normal 500 15px/18px ${fonts.golos}`,
      color: colorTheme.palette.grey250.main,
    },
    medium14: {
      font: `normal 500 14px/18px ${fonts.inter}`,
      color: colorTheme.palette.grey200.main,
    },
    regular16: {
      font: `normal 500 16px/18px ${fonts.inter}`,
      color: colorTheme.palette.grey300.main,
    },
    bodyText18: {
      font: `normal 400 18px/30px ${fonts.golos}`,
      color: colorTheme.palette.darkPrimary.main,
    },
    bodyText16: {
      font: `normal 400 16px/26px ${fonts.golos}`,
      color: colorTheme.palette.darkPrimary.main,
    },
    bold22: {
      font: `normal 700 22px/normal ${fonts.golos}`,
      color: colorTheme.palette.grey250.main,
    },
    demi18: {
      font: `normal 600 18px/normal ${fonts.golos}`,
      color: colorTheme.palette.darkPrimary.main,
    },
    button: {
      font: `normal 600 18px/normal ${fonts.golos}`,
      color: colorTheme.palette.white.main,
    },
    mobileText: {
      font: `normal 400 15px/25px ${fonts.golos}`,
      color: colorTheme.palette.darkPrimary.main,
    },
  },
});

export default theme;
