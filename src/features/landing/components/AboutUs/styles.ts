import breakpointsTheme from '@/components/UiBreakpoints';

export default {
  wrapper: {
    pt: '2rem',
    position: 'relative',
    maxWidth: '100dvw',
    [`@media (min-width: ${breakpointsTheme.breakpoints.values.md}px)`]: {
      pt: '4.875rem',
    },
    [`@media (min-width: ${breakpointsTheme.breakpoints.values.xl}px)`]: {
      pt: '5rem',
    },
  },

  content: {
    display: 'flex',
    flexDirection: 'column',
    alignItems: 'center',
  },
};
