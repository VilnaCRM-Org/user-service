import breakpointsTheme from '@/components/UiBreakpoints';

export default {
  wrapper: {
    pt: '9rem',
    position: 'relative',
    maxWidth: '100dvw',
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.xl}px)`]: {
      pt: '9.125rem',
    },
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.sm}px)`]: {
      pt: '5.5rem',
    },
  },
  content: {
    display: 'flex',
    flexDirection: 'column',
    alignItems: 'center',
  },
};
