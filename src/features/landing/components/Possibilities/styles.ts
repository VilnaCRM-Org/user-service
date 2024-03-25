import breakpointsTheme from '@/components/UiBreakpoints';

export default {
  wrapper: {
    pt: '4.375rem',
    pb: '3.625rem',
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.lg}px)`]: {
      pb: '3.375rem',
    },
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.sm}px)`]: {
      pb: '1.75rem',
      pt: '4.063rem',
      ml: '0.563rem',
    },
  },
};
