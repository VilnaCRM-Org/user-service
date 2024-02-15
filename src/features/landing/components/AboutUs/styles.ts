import breakpointsTheme from '@/components/UiBreakpoints';

export default {
  wrapper: {
    pt: '5.5rem',
    position: 'relative',
    maxWidth: '100dvw',
    [`@media (min-width: ${breakpointsTheme.breakpoints.values.md}px)`]: {
      pt: '9.125rem',
    },
    [`@media (min-width: ${breakpointsTheme.breakpoints.values.xl}px)`]: {
      pt: '9rem',
    },
  },
  content: {
    display: 'flex',
    flexDirection: 'column',
    alignItems: 'center',
  },
};
