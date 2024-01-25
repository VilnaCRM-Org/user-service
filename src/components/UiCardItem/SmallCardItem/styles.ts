import { colorTheme } from '@/components/UiColorTheme';

export default {
  wrapper: {
    padding: '2.5rem 2rem 2.5rem 1.563rem',
    borderRadius: '0.75rem',
    border: `1px solid ${colorTheme.palette.grey500.main}`,
    maxHeight: '20.75rem',
    '@media (max-width: 1439.98px)': {
      padding: '2.125rem 1.875rem 2.125rem 1.563rem',
      flexDirection: 'row',
      alignItems: 'center',
      gap: '2.813rem',
      maxHeight: '11.375rem',
    },
    '@media (max-width: 639.98px)': {
      flexDirection: 'column',
      padding: '1rem 1.125rem 2.5rem 1rem ',
      gap: '1rem',
      alignItems: 'start',
      minHeight: '15.125rem',
    },
  },

  title: {
    pt: '2rem',
    '@media (max-width: 1439.98px)': {
      pt: '0',
    },
    '@media (max-width: 1023.98px)': {
      fontSize: '1.125rem',
      fontWeight: '600',
    },
  },

  text: {
    mt: '0.625rem',
    '@media (max-width: 1439.98px)': {
      a: {
        textDecoration: 'none',
        fontWeight: '400',
        color: colorTheme.palette.darkPrimary.main,
      },
    },
    '@media (max-width: 1023.98px)': {
      fontSize: '0.9375rem',
      fontWeight: '400',
      lineHeight: '1.563rem',
      mt: '0.75rem',
    },
  },
  image: {
    width: '5rem',
    height: '5rem',
    '@media (max-width: 639.98px)': {
      width: '3.125rem',
      height: '3.125rem',
    },
  },
};
