import breakpointsTheme from '@/components/UiBreakpoints';
import colorTheme from '@/components/UiColorTheme';

export default {
  title: {
    [`@media (max-width: 968px)`]: {
      color: colorTheme.palette.darkPrimary.main,
      fontSize: '1.75rem',
      fontStyle: 'normal',
      fontHeight: '700',
      lineHeight: 'normal',
    },
  },

  description: {
    pt: '1rem',
    pb: '1.5rem',
    maxWidth: '21.438rem',
    '@media (max-width: 1130.98px)': {
      pb: '2rem',
      maxWidth: '18.938rem',
    },
    [`@media (max-width: 968px)`]: {
      pt: '0.813rem',
      color: colorTheme.palette.darkPrimary.main,
      fontSize: '0.9375rem',
      fontStyle: 'normal',
      fontSeight: '400',
      lineHeight: '1.563rem',
      pb: '0',
      maxWidth: '100%',
      paddingBottom: '10.875rem',
    },
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.sm}px)`]: {
      paddingBottom: '0',
    },
  },

  button: {
    zIndex: 2,
    display: 'inline-block',
    [`@media (max-width: 968px)`]: {
      display: 'none',
    },
  },
};
