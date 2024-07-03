import breakpointsTheme from '@/components/UiBreakpoints';
import colorTheme from '@/components/UiColorTheme';

export default {
  wrapper: {
    paddingTop: '2.1875rem',
    backgroundColor: colorTheme.palette.backgroundGrey100.main,
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.lg}px)`]: {
      paddingTop: '1.5rem',
    },
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.md}px)`]: {
      paddingTop: '1.0635rem',
    },
  },
};
