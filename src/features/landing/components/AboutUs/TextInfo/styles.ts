import { colorTheme } from '@/components/UiColorTheme';

export default {
  textWrapper: {
    maxWidth: '43.813rem',
    mb: '3.125rem',
    '@media (max-width: 1439.98px)': {
      mb: '3.438rem',
      ml: '1.75rem',
    },
    '@media (max-width: 639.98px)': {
      mb: '3.063rem',
      ml: '0',
    },
  },
  title: {
    textAlign: 'center',
    '@media (max-width: 639.98px)': {
      color: colorTheme.palette.darkPrimary.main,
      fontFamily: 'Golos',
      fontSize: '2rem',
      fontStyle: 'normal',
      fontWeight: '700',
      lineHeight: 'normal',
      textAlign: 'left',
    },
  },
  text: {
    mt: '1rem',
    textAlign: 'center',
    mb: '2.438rem',
    '@media (max-width: 639.98px)': {
      maxWidth: '21.313rem',
      mt: '0.75rem',
      mb: '1.5rem',
      textAlign: 'left',
      color: colorTheme.palette.darkPrimary.main,
      fontFamily: 'Golos',
      fontSize: '0.9375rem',
      fontStyle: 'normal',
      fontWeight: '400',
      lineHeight: '1.563rem',
    },
  },
  button: {
    '@media (max-width: 419.98px)': {
      alignSelf: 'start',
      marginBottom: '1.375rem',
    },
  },
};
