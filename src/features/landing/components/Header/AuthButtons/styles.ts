import breakpointsTheme from '@/components/UiBreakpoints';

export default {
  wrapper: {
    display: 'inline-block',
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.lg}px)`]: {
      display: 'none',
    },
  },
};
