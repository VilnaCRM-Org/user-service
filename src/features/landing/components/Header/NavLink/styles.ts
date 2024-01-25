import { colorTheme } from '@/components/UiColorTheme';

export default {
  wrapper: {
    ml: '6.625rem',
    display: 'inline-block',
    '@media (max-width: 1439.98px)': {
      ml: '0',
      mr: '6rem',
    },
    '@media (max-width: 1023.98px)': {
      display: 'none',
    },
  },
  content: {
    display: 'flex',
  },
  navLink: {
    textDecoration: 'none',
    color: colorTheme.palette.grey250.main,
  },
};
