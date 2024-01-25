import { colorTheme } from '@/components/UiColorTheme';

export default {
  headerWrapper: {
    backgroundColor: colorTheme.palette.white.main,
    boxShadow: 'none',
    position: 'fixed',
    zIndex: 100,
  },
  logo: {
    width: '8.188rem',
    height: '2.75rem',
    '@media (max-width: 1439.98px)': {
      width: '9.313rem',
      height: '3.125rem',
    },
    '@media (max-width: 1023.98px)': {
      width: '8.188rem',
      height: '2.75rem',
    },
  },
};
