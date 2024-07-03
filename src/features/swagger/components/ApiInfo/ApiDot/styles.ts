import breakpointsTheme from '@/components/UiBreakpoints';
import colorTheme from '@/components/UiColorTheme';

export default {
  dot: {
    height: '0.25rem',
    width: '0.25rem',
    borderRadius: '50%',
    backgroundColor: colorTheme.palette.darkSecondary.main,
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.md}px)`]: {
      display: 'none',
    },
  },
};
