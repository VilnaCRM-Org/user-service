import breakpointsTheme from '../UiBreakpoints';

export default {
  smallGrid: {
    display: 'grid',
    marginTop: '2rem',
    gap: '0.75rem',
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.sm}px)`]: {
      display: 'none',
    },
    [`@media (min-width: ${breakpointsTheme.breakpoints.values.md}px)`]: {
      gridTemplateColumns: 'repeat(2, 1fr)',
    },
    [`@media (min-width: ${breakpointsTheme.breakpoints.values.xl}px)`]: {
      gridTemplateColumns: 'repeat(4, 289px)',
    },
  },

  gridSmallMobile: {
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

  largeGrid: {
    display: 'grid',
    marginTop: '2.5rem',
    gap: '0.813rem',
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.lg}px)`]: {
      gap: '0.75rem',
      marginTop: '2rem',
    },
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.sm}px)`]: {
      display: 'none',
    },
    [`@media (min-width: ${breakpointsTheme.breakpoints.values.md}px)`]: {
      gridTemplateRows: 'repeat(2, 1fr)',
      gridTemplateColumns: 'repeat(2, 1fr)',
    },
    [`@media (min-width: ${breakpointsTheme.breakpoints.values.lg}px)`]: {
      gridTemplateRows: 'repeat(2, minmax(23.75rem, auto))',
      gridTemplateColumns: 'repeat(3, minmax(15.625rem, 24.3125rem))',
    },
    [`@media (min-width: ${breakpointsTheme.breakpoints.values.xl}px)`]: {
      gridTemplateRows: 'repeat(2, minmax(21.375rem, auto))',
    },
  },

  gridLargeMobile: {
    '& .swiper .swiper-pagination .swiper-pagination-bullet': {
      marginRight: '1.25rem',
    },
    '& .swiper .swiper-pagination': {
      marginLeft: '0.5rem',
    },
    display: 'none',
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.sm}px)`]: {
      display: 'grid',
      marginTop: '1.5rem',
      minHeight: '19.313rem',
    },
  },

  gridContainerLargeScreen: {
    display: 'none',
    [`@media (min-width: ${breakpointsTheme.breakpoints.values.sm}px)`]: {
      display: 'block',
    },
  },

  swiperContainerSmallScreen: {
    display: 'none',
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.sm}px)`]: {
      display: 'block',
    },
  },
};
