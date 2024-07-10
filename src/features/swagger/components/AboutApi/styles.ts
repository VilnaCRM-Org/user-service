import breakpointsTheme from '@/components/UiBreakpoints';

export default {
  aboutApiWrapper: {
    marginBottom: '1.625rem',
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.lg}px)`]: {
      marginBottom: '1.75rem',
    },
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.md}px)`]: {
      marginBottom: '0.75rem',
    },
  },
};
