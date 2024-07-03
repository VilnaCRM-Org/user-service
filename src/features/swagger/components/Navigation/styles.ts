import breakpointsTheme from '@/components/UiBreakpoints';

export default {
  navigationWrapper: {
    marginBottom: '0.625rem',
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.md}px)`]: {
      marginBottom: '1.063rem',
    },
  },
  navigationButton: {
    alignItems: 'center',
    gap: '0.5rem',
    cursor: 'pointer',
    width: 'fit-content',
  },
  navigationText: {
    fontSize: '0.9375rem',
  },
};
