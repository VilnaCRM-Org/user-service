import breakpointsTheme from '@/components/UiBreakpoints';
import colorTheme from '@/components/UiColorTheme';

export default {
  wrapper: {
    gap: '0.5rem',
    flexDirection: 'row',
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.md}px)`]: {
      flexDirection: 'column',
      gap: '0.25rem',
      pt: '0.25rem',
    },
  },

  textColor: {
    color: colorTheme.palette.grey300.main,
  },

  privacy: {
    color: 'inherit',
    textDecoration: 'none',
    padding: '0.5rem 1rem',
    borderRadius: '0.5rem',
    background: colorTheme.palette.backgroundGrey200.main,
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.md}px)`]: {
      textAlign: 'center',
      width: '100%',
      padding: '1.063rem 0 1.125rem',
    },
    '&:hover': {
      background: colorTheme.palette.grey500.main,
    },
  },

  usage_policy: {
    color: 'inherit',
    textDecoration: 'none',
    padding: '0.5rem 1rem',
    borderRadius: '0.5rem',
    background: colorTheme.palette.backgroundGrey200.main,
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.md}px)`]: {
      textAlign: 'center',
      width: '100%',
      padding: '1.063rem 0 1.125rem',
    },
    '&:hover': {
      background: colorTheme.palette.grey500.main,
    },
  },
};
