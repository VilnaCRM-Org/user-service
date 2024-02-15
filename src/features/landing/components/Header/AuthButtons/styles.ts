import breakpointsTheme from '@/components/UiBreakpoints';

export default {
  wrapper: {
    display: 'none',
    [`@media (min-width: ${breakpointsTheme.breakpoints.values.lg}px)`]: {
      display: 'inline-block',
    },
  },
};
