export default {
  grid: {
    display: 'grid',
    gridTemplateColumns: 'repeat(4, 1fr)',
    marginTop: '2rem',
    gap: '0.75rem',
    '@media (max-width: 1439.98px)': {
      gridTemplateColumns: 'repeat(2, 1fr)',
    },
    '@media (max-width: 767.98px)': {
      gridTemplateColumns: 'repeat(1, 1fr)',
    },
    '@media (max-width: 639.98px)': {
      display: 'none',
    },
  },

  gridMobile: {
    '& .swiper .swiper-pagination .swiper-pagination-bullet': {
      marginRight: '1.25rem',
    },
    display: 'none',
    '@media (max-width: 639.98px)': {
      minHeight: '18.5rem',
      display: 'grid',
      marginTop: '1.5rem',
      gap: '0.75rem',
    },
  },
};
