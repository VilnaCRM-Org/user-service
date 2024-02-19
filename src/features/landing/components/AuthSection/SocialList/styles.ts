export default {
  listWrapper: {
    display: 'grid',
    maxWidth: '24.375rem',
    gridTemplateColumns: 'repeat(2, 1fr)',
    gap: '0.75rem',
    [`@media (max-width: 1130px)`]: {
      maxWidth: '100%',
      gridTemplateColumns: 'repeat(4, 1fr)',
    },
    [`@media (max-width: 968px)`]: {
      gridTemplateColumns: 'repeat(2, 1fr)',
      columnGap: '0.5rem',
      rowGap: '0.75rem',
    },
  },
};
