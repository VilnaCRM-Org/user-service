import breakpointsTheme from '@/components/UiBreakpoints';

export default {
  grid: {
    display: 'grid',
    gridTemplateColumns: {
      xl: 'repeat(4, 1fr)',
      md: 'repeat(2, 1fr)',
    },
    marginTop: '2rem',
    gap: '0.75rem',
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.sm}px)`]: {
      display: 'none',
    },
  },
  gridMobile: {
    display: 'none',
    '& .swiper .swiper-pagination .swiper-pagination-bullet': {
      marginRight: '1.25rem',
    },
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.sm}px)`]: {
      minHeight: '18.5rem',
      display: 'grid',
      marginTop: '1.5rem',
      gap: '0.75rem',
    },
  },
};
