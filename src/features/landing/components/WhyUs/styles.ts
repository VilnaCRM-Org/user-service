import breakpointsTheme from '@/components/UiBreakpoints';

export default {
  wrapper: {
    pb: '9.063rem',
    paddingTop: '7.125rem',
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.lg}px)`]: {
      pb: '0',
    },
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.sm}px)`]: {
      paddingTop: '4rem',
    },
  },
};
