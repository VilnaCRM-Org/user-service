import breakpointsTheme from '@/components/UiBreakpoints';

export default {
  title: {
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.sm}px)`]: {
      fontSize: '1.75rem',
      fontStyle: 'normal',
      fontWeight: '700',
      lineHeight: 'normal',
    },
  },
  text: {
    marginTop: '1rem',
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.sm}px)`]: {
      marginTop: '0.5rem',
      fontSize: '0.9375rem',
      fontStyle: 'normal',
      fontWeight: '400',
      lineHeight: '1.563rem',
    },
  },
};
