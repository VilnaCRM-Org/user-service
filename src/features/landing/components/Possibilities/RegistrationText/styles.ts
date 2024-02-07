import breakpointsTheme from '@/components/UiBreakpoints';
import colorTheme from '@/components/UiColorTheme';

export default {
  textWrapper: {
    paddingRight: '2rem',
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.sm}px)`]: {
      paddingRight: '0',
    },
  },
  title: {
    mb: '0.438rem',
    padding: '0.75rem 2rem',
    alignSelf: 'center',
    borderRadius: '1rem',
    backgroundColor: colorTheme.palette.secondary.main,
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.sm}px)`]: {
      padding: '0.75rem 1.5rem',
      alignSelf: 'start',
      fontSize: '1.375rem',
      fontWeight: '700',
    },
  },
  text: {
    textAlign: 'center',
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.sm}px)`]: {
      textAlign: 'left',
      fontSize: '1.75rem',
      fontWeight: '700',
    },
  },
};
