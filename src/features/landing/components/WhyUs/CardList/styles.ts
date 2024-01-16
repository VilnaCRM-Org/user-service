export const cardListStyles = {
  grid: {
    display: 'grid',
    gridTemplateColumns: {
      md: 'repeat(2, 1fr)',
      lg: 'repeat(3, minmax(15.625rem, 24.3125rem))',
    },
    gridTemplateRows: {
      md: 'repeat(2, 1fr)',
      lg: 'repeat(2, minmax(380px, auto))',
      xl: 'repeat(2, minmax(342px, auto))',
    },
    marginTop: '2.5rem',
    gap: '13px',
    '@media (max-width: 1439.98px)': {
      gap: '12px',
      marginTop: '2rem',
    },
    '@media (max-width: 639.98px)': {
      display: 'none',
    },
  },
  gridMobile: {
    display: 'none',
    '@media (max-width: 639.98px)': {
      display: 'grid',
      marginTop: '24px',
    },
  },
  button: {
    maxWidth: '137px',
    margin: '0 auto',
    marginTop: '14px',
  },
};
