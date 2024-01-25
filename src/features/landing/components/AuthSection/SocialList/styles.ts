export default {
  listWrapper: {
    display: 'grid',
    maxWidth: '24.375rem',
    gridTemplateColumns: 'repeat(2, 1fr)',
    gap: '0.75rem',
    '@media (max-width: 1439.98px)': {
      maxWidth: '100%',
      gridTemplateColumns: 'repeat(4,1fr) ',
    },
    '@media (max-width: 1023.98px)': {
      gridTemplateColumns: 'repeat(2, 1fr)',
    },
    '@media (max-width: 639.98px)': {
      columnGap: '0.5rem',
      rowGap: '0.75rem',
    },
  },
};
