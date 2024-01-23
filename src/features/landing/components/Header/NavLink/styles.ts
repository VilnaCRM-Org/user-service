import { colorTheme } from '@/components/UiColorTheme';

export const navLinkStyles = {
  wrapper: {
    ml: '106px',
    display: 'inline-block',
    '@media (max-width: 1439.98px)': {
      ml: '0',
      mr: '96px',
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
