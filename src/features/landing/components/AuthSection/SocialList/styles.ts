export default {
  listWrapper: {
    display: 'grid',
    gridTemplateColumns: 'repeat(2, minmax(120px, 189px))',
    gap: '0.75rem',
    [`@media (max-width: 1130px)`]: {
      maxWidth: '100%',
      gridTemplateColumns: 'repeat(4, minmax(120px, 189px))',
    },
    [`@media (max-width: 968px)`]: {
      gridTemplateColumns: 'repeat(2, minmax(120px, 189px))',
      columnGap: '0.5rem',
      rowGap: '0.75rem',
    },
  },
};
