export default {
  grid: {
    display: 'grid',
    gridTemplateColumns: {
      md: 'repeat(2, 1fr)',
      lg: 'repeat(3, minmax(15.625rem, 24.3125rem))',
    },
    gridTemplateRows: {
      md: 'repeat(2, 1fr)',
      lg: 'repeat(2, minmax(23.75rem, auto))',
      xl: 'repeat(2, minmax(21.375rem, auto))',
    },
    marginTop: '2.5rem',
    gap: '0.813rem',
    '@media (max-width: 1439.98px)': {
      gap: '0.75rem',
      marginTop: '2rem',
    },
    '@media (max-width: 639.98px)': {
      display: 'none',
    },
  },
  gridMobile: {
    '& .swiper .swiper-pagination .swiper-pagination-bullet': {
      marginRight: '1.25rem',
    },
    '& .swiper .swiper-pagination': {
      marginLeft: '0.5rem',
    },
    display: 'none',
    '@media (max-width: 639.98px)': {
      display: 'grid',
      marginTop: '1.5rem',
      paddingBottom: '2rem',
    },
  },
  button: {
    maxWidth: '8.563rem',
    margin: '0 auto',
    marginTop: '0.875rem',
  },
};
