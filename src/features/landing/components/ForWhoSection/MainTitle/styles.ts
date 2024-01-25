import { colorTheme } from '@/components/UiColorTheme';

export default {
  title: {
    '@media (max-width: 1023.98px)': {
      color: colorTheme.palette.darkPrimary.main,
      fontFamily: 'Golos',
      fontSize: '1.75rem',
      fontStyle: 'normal',
      fontHeight: '700',
      lineHeight: 'normal',
    },
  },
  description: {
    pt: '1rem',
    pb: '1.5rem',
    '@media (max-width: 1130.98px)': {
      pb: '2rem',
      maxWidth: '18.938rem',
    },
    '@media (max-width: 1023.98px)': {
      pt: '0.813rem',
      color: colorTheme.palette.darkPrimary.main,
      fontfamily: 'Golos',
      fontSize: '0.9375rem',
      fontStyle: 'normal',
      fontSeight: '400',
      lineHeight: '1.563rem',
      pb: '0',
      maxWidth: '100%',
      paddingBottom: '10.875rem',
    },
    '@media (max-width: 639.98px)': {
      paddingBottom: '0',
    },
  },
  button: {
    display: 'inline-block',
    '@media (max-width: 1023.98px)': {
      display: 'none',
    },
  },
};
