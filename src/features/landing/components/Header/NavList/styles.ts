import breakpointsTheme from '@/components/UiBreakpoints';

export default {
  wrapper: {
    display: 'none',
    [`@media (min-width: ${breakpointsTheme.breakpoints.values.lg}px)`]: {
      display: 'inline-block',
      marginRight: '6rem',
    },
    [`@media (min-width: ${breakpointsTheme.breakpoints.values.xl}px)`]: {
      marginLeft: '6.625rem',
      marginRight: '0',
    },
  },
  drawerWrapper: {
    marginTop: '1rem',
  },
  content: {
    display: 'flex',
  },
  drawerContent: {
    display: 'flex',
    flexDirection: 'column',
    gap: '0.375rem',
    padding: '0',
  },
};
