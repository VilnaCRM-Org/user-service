import breakpointsTheme from '@/components/UiBreakpoints';
import colorTheme from '@/components/UiColorTheme';

export default {
  gmailText: {
    color: colorTheme.palette.darkSecondary.main,
    textAlign: 'center',
    width: '100%',
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.md}px)`]: {
      fontSize: '1.125rem',
      fontStyle: 'normal',
      fontWeight: '600',
      lineHeight: 'normal',
    },
  },

  gmailWrapper: {
    padding: '0.5rem 1rem',
    borderRadius: '0.5rem',
    background: colorTheme.palette.white.main,
    border: `1px solid ${colorTheme.palette.grey400.main}`,
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.md}px)`]: {
      padding: '0.875rem 0 0.9375rem',
    },
  },
};
