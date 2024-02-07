import breakpointsTheme from '@/components/UiBreakpoints';

export default {
  grid: {
    display: 'grid',
    gridTemplateColumns: 'repeat(4, 1fr)',
    marginTop: '2rem',
    gap: '0.75rem',
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.xl}px)`]: {
      gridTemplateColumns: 'repeat(2, 1fr)',
    },
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.md}px)`]: {
      gridTemplateColumns: 'repeat(1, 1fr)',
    },
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.sm}px)`]: {
      display: 'none',
    },
  },

  gridMobile: {
    '& .swiper .swiper-pagination .swiper-pagination-bullet': {
      marginRight: '1.25rem',
    },
    display: 'none',
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.sm}px)`]: {
      minHeight: '18.5rem',
      display: 'grid',
      marginTop: '1.5rem',
      gap: '0.75rem',
    },
  },
};
