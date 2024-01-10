export const cardListStyles = {
  grid: {
    display: 'grid',
    gridTemplateColumns: 'repeat(4, 1fr)',
    marginTop: '2rem',
    gap: '0.75rem',
    '@media (max-width: 1439.98px)': {
      gridTemplateColumns: 'repeat(2, 1fr)',
    },
    '@media (max-width: 1023.98px)': {
      gridTemplateColumns: 'repeat(1, 1fr)',
    },
    '@media (max-width: 639.98px)': {
      display: 'none',
    },
  },

  gridMobile: {
    display: 'none',
    '@media (max-width: 639.98px)': {
      minHeight: '282px',
      display: 'grid',
      marginTop: '2rem',
      gap: '0.75rem',
    },
  },
};
