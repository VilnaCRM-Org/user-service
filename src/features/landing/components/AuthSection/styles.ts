import breakpointsTheme from '@/components/UiBreakpoints';
import colorTheme from '@/components/UiColorTheme';

export default {
  wrapper: {
    background: colorTheme.palette.backgroundGrey100.main,
    mb: '0.125rem',
    position: 'relative',
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.xl}px)`]: {
      mb: '0',
    },
  },
  content: {
    flexDirection: 'row',
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.xl}px)`]: {
      flexDirection: 'column',
      alignItems: 'center',
    },
  },
};
