import { createTheme } from '@mui/material';

const fonts = {
  golos: 'Golos',
  inter: 'Inter',
};

const hStyles = {
  color: '#1A1C1E',
  fontFamily: fonts.golos,
  fontWeight: '700',
};

const mediumStyles = {
  color: '#484848',
  lineHeight: '18px',
};

const textStyles = {
  lineHeight: 'normal',
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
      ...mediumStyles,
      fontFamily: fonts.inter,
      fontSize: '16px',
      fontWeight: '500',
    },
    medium15: {
      ...mediumStyles,
      fontFamily: fonts.golos,
      fontStyle: 'normal',
      fontSize: '15px',
      fontWeight: '500',
      color: '#57595B',
    },
    medium14: {
      ...mediumStyles,
      fontFamily: fonts.inter,
      fontSize: '14px',
      color: '#404142',
    },
    regular16: {
      ...mediumStyles,
      fontFamily: fonts.inter,
      fontWeight: '400',
    },
    bodyText18: {
      ...textStyles,
      fontFamily: fonts.golos,
      fontSize: '18px',
      fontWeight: '400',
      lineHeight: '30px',
    },
    bodyText16: {
      ...textStyles,
      fontFamily: fonts.golos,
      fontSize: '16px',
      lineHeight: '26px',
    },
    bold22: {
      ...textStyles,
      fontFamily: fonts.golos,
      fontSize: '22px',
      fontWeight: '700',
      color: '#57595B',
    },
    demi18: {
      ...textStyles,
      fontFamily: fonts.golos,
      fontSize: '18px',
      fontWeight: '600',
    },
    button: {
      color: '#FFF',
      fontFamily: fonts.golos,
      fontSize: '18px',
      fontStyle: 'normal',
      fontWeight: '600',
      lineHeight: 'normal',
    },
    bodyMobile: {
      color: '#1A1C1E',
      fontFamily: fonts.golos,
      fontSize: '15px',
      fontStyle: 'normal',
      fontWeight: '400',
      lineHeight: '25px',
    },
  },
});

export default theme;
