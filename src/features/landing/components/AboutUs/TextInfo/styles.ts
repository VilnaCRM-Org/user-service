import breakpointsTheme from '@/components/UiBreakpoints';
import colorTheme from '@/components/UiColorTheme';

export default {
  textWrapper: {
    maxWidth: '43.813rem',
    mb: '3.063rem',
    [`@media (min-width: ${breakpointsTheme.breakpoints.values.sm}px)`]: {
      mb: '3.438rem',
      ml: '1.75rem',
    },
    [`@media (min-width: ${breakpointsTheme.breakpoints.values.xl}px)`]: {
      mb: '3.125rem',
      ml: '0',
    },
  },

  title: {
    textAlign: 'center',
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.sm}px)`]: {
      color: colorTheme.palette.darkPrimary.main,
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
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.sm}px)`]: {
      maxWidth: '21.313rem',
      mt: '0.75rem',
      mb: '1.5rem',
      textAlign: 'left',
      color: colorTheme.palette.darkPrimary.main,
      fontSize: '0.9375rem',
      fontStyle: 'normal',
      fontWeight: '400',
      lineHeight: '1.563rem',
    },
  },

  link: {
    alignSelf: 'center',
    '@media (max-width: 419.98px)': {
      alignSelf: 'flex-start',
    },
  },

  button: {
    marginBottom: '1.375rem',
  },
};
