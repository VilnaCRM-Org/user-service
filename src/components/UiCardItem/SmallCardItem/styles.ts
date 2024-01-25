import { colorTheme } from '@/components/UiColorTheme';

export default {
  wrapper: {
    padding: '40px 32px 40px 25px',
    borderRadius: '0.75rem',
    border: `1px solid ${colorTheme.palette.grey500.main}`,
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
        color: colorTheme.palette.darkPrimary.main,
      },
    },
    '@media (max-width: 1023.98px)': {
      fontSize: '15px',
      fontWeight: '400',
      lineHeight: '25px',
      mt: '12px',
    },
  },
  image: {
    width: '80px',
    height: '80px',
    '@media (max-width: 639.98px)': {
      width: '50px',
      height: '50px',
    },
  },
};
