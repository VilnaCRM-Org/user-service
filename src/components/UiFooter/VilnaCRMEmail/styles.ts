import breakpointsTheme from '../../UiBreakpoints';
import colorTheme from '../../UiColorTheme';

export default {
  emailText: {
    color: colorTheme.palette.darkSecondary.main,
    textAlign: 'center',
    width: '100%',
    textDecoration: 'none',
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.md}px)`]: {
      fontSize: '1.125rem',
      fontStyle: 'normal',
      fontWeight: '600',
      lineHeight: 'normal',
    },
  },

  emailLink: {
    color: 'inherit',
    textDecoration: 'none',
  },

  emailWrapper: {
    padding: '0.5rem 1rem',
    borderRadius: '0.5rem',
    background: colorTheme.palette.white.main,
    border: `1px solid ${colorTheme.palette.grey400.main}`,
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.md}px)`]: {
      padding: '0.875rem 0 0.9375rem',
    },
  },
};
