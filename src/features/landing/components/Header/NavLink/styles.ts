import breakpointsTheme from '@/components/UiBreakpoints';
import colorTheme from '@/components/UiColorTheme';

export default {
  wrapper: {
    ml: '6.625rem',
    display: 'inline-block',
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.xl}px)`]: {
      ml: '0',
      mr: '6rem',
    },
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.lg}px)`]: {
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
