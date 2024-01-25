import { colorTheme } from '@/components/UiColorTheme';

export default {
  wrapper: {
    background: colorTheme.palette.backgroundGrey100.main,
    mb: '2px',
    position: 'relative',
    '@media (max-width: 1439.98px)': {
      mb: '0',
    },
  },
  content: {
    flexDirection: 'row',
    '@media (max-width: 1439.98px)': {
      flexDirection: 'column',
      alignItems: 'center',
    },
  },
};
