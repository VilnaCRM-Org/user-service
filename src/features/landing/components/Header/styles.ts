import breakpointsTheme from '@/components/UiBreakpoints';
import colorTheme from '@/components/UiColorTheme';

export default {
  headerWrapper: {
    backgroundColor: colorTheme.palette.white.main,
    boxShadow: 'none',
    position: 'fixed',
    zIndex: 3000,
  },

  logo: {
    width: '8.188rem',
    height: '2.75rem',
    [`@media (min-width: ${breakpointsTheme.breakpoints.values.md}px)`]: {
      width: '9.313rem',
      height: '3.125rem',
    },
    [`@media (min-width: ${breakpointsTheme.breakpoints.values.xl}px)`]: {
      width: '8.188rem',
      height: '2.75rem',
    },
  },
};
