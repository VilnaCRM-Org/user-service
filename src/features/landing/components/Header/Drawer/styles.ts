import breakpointsTheme from '@/components/UiBreakpoints';

export default {
  wrapper: {
    display: 'none',
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.lg}px)`]: {
      display: 'inline-block',
    },
  },
  drawerContent: {
    maxWidth: '23.4375rem',
    width: '100dvw',
    px: '0.938rem',
    py: '0.375rem',
  },

  button: {
    minWidth: '0',
    padding: '0',
  },
};
