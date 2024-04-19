import colorTheme from '@/components/UiColorTheme';

export default {
  link: {
    textDecoration: 'none',
    color: colorTheme.palette.grey250.main,
  },

  drawerLink: {
    width: '100%',
    textDecoration: 'none',
    display: 'flex',
    alignItems: 'center',
    justifyContent: 'space-between',
    borderRadius: '0.5rem',
    background: colorTheme.palette.backgroundGrey300.main,
    padding: '1.188rem 1.25rem',
    '&:hover': {
      background: colorTheme.palette.grey500.main,
    },
  },

  navText: {
    color: colorTheme.palette.grey250.main,
    '&:hover': {
      color: colorTheme.palette.grey200.main,
    },
  },

  itemDrawerWrapper: {
    padding: '0',
  },
};
