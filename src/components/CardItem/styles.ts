export const cardItemWhyUsStyles = {
  wrapper: {
    height: '100%',
    '@media (max-width: 639.98px)': {
      height: '308px',
    },
  },
  content: {
    height: '100%',
    p: '1.5rem',
    borderRadius: '0.75rem',
    border: '1px solid #EAECEE',
    ':hover': {
      boxShadow: '0px 8px 27px 0px rgba(49, 59, 67, 0.14)',
      border: '1px solid #D0D4D8',
    },
    '@media (max-width: 639.98px)': {
      p: '16px 18px 72px 16px',
      borderRadius: '0.75rem',
      border: '1px solid #EAECEE',
      maxHeight: '263px',
    },
  },
  title: {
    pt: '16px',
    '@media (max-width: 1439.98px)': {
      fontSize: '22px',
    },
    '@media (max-width: 639.98px)': {
      pt: '16px',
      fontSize: '18px',
    },
  },
  text: {
    mt: '12px',
    '@media (max-width: 639.98px)': {
      fontSize: '15px',
      lineHeight: '25px',
    },
  },
  image: {
    backgroundSize: 'cover',
    width: '70px',
    height: '70px',
    '@media (max-width: 639.98px)': {
      width: '50px',
      height: '50px',
    },
  },
};

export const cardItemPossibilitiesStyles = {
  wrapper: {
    padding: '40px 32px 40px 25px',
    borderRadius: '0.75rem',
    border: '1px solid #EAECEE',
    maxHeight: '332px',
    '@media (max-width: 1439.98px)': {
      padding: '34px 30px 34px 25px',
      flexDirection: 'row',
      alignItems: 'center',
      gap: '45px',
      maxHeight: '182px',
    },
    '@media (max-width: 639.98px)': {
      flexDirection: 'column',
      padding: '16px 18px 40px 16px ',
      gap: '16px',
      alignItems: 'start',
      minHeight: '242px',
      marginRight: '5px',
    },
  },

  title: {
    pt: '32px',
    '@media (max-width: 1439.98px)': { pt: '0' },
    '@media (max-width: 1023.98px)': {
      fontSize: '18px',
      fontWeight: '600',
    },
  },

  text: {
    mt: '10px',
    '@media (max-width: 1439.98px)': {
      a: {
        textDecoration: 'none',
        fontWeight: '400',
        color: '#1A1C1E',
      },
    },
    '@media (max-width: 1023.98px)': {
      fontSize: '15px',
      fontWeight: '400',
      lineHeight: '25px',
      mt: '12px',
    },
  },
};
