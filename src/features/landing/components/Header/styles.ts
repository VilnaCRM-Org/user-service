import breakpointsTheme from '@/components/UiBreakpoints';
import colorTheme from '@/components/UiColorTheme';

export default {
  headerWrapper: {
    backgroundColor: colorTheme.palette.white.main,
    boxShadow: 'none',
    position: 'fixed',
    zIndex: 100,
  },
  logo: {
    width: '8.188rem',
    height: '2.75rem',
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.xl}px)`]: {
      width: '9.313rem',
      height: '3.125rem',
    },
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.lg}px)`]: {
      width: '8.188rem',
      height: '2.75rem',
    },
  },
};
