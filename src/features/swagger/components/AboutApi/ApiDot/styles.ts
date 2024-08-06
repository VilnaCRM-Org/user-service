import breakpointsTheme from '@/components/UiBreakpoints';

export default {
  dot: {
    height: '0.25rem',
    width: '0.25rem',
    borderRadius: '50%',
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.md}px)`]: {
      display: 'none',
    },
  },
};
