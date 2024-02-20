import breakpointsTheme from '@/components/UiBreakpoints';

export default {
  mainImageWrapper: {
    overflow: 'hidden',
    borderTopRightRadius: '0.625rem',
    borderTopLeftRadius: '0.625rem',
    marginTop: '-1.125rem',
    height: '31.125rem',
    width: '47.875rem',
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.lg}px)`]: {
      borderRadius: '0.625rem',
      width: '28.125rem',
      height: '32.813rem',
      marginTop: '0',
    },
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.sm}px)`]: {
      width: '12.75rem',
      height: '27.25rem',
      marginTop: '-1.125rem',
    },
  },
};
