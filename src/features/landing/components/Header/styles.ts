import { colorTheme } from '@/components/UiColorTheme';

export default {
  headerWrapper: {
    backgroundColor: colorTheme.palette.white.main,
    boxShadow: 'none',
    position: 'fixed',
    zIndex: 100,
  },
  logo: {
    width: '131px',
    height: '44px',
    '@media (max-width: 1439.98px)': {
      width: '149px',
      height: '50px',
    },
    '@media (max-width: 1023.98px)': {
      width: '131px',
      height: '44px',
    },
  },
};
