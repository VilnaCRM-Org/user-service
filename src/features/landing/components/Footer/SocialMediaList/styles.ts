import breakpointsTheme from '@/components/UiBreakpoints';

export default {
  listWrapper: {
    gap: '0.5rem',
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.md}px)`]: {
      gap: '0.25rem',
      mt: '0.188rem',
    },
  },
};
