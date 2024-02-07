import breakpointsTheme from '@/components/UiBreakpoints';

export default {
  listWrapper: {
    display: 'grid',
    maxWidth: '24.375rem',
    gridTemplateColumns: 'repeat(2, 1fr)',
    gap: '0.75rem',
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.xl}px)`]: {
      maxWidth: '100%',
      gridTemplateColumns: 'repeat(4,1fr) ',
    },
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.lg}px)`]: {
      gridTemplateColumns: 'repeat(2, 1fr)',
    },
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.sm}px)`]: {
      columnGap: '0.5rem',
      rowGap: '0.75rem',
    },
  },
};
