import { colorTheme } from '@/components/UiColorTheme';

export default {
  wrapper: {
    gap: '0.5rem',
    flexDirection: 'row',
    '@media (max-width: 1439.98px)': {
      marginRight: '-0.5rem',
    },
    '@media (max-width: 767.98px)': {
      marginRight: '0',
      flexDirection: 'column',
      gap: '0.25rem',
      pt: '0.25rem',
    },
  },
  privacy: {
    textDecoration: 'none',
    padding: '0.5rem 1rem',
    borderRadius: '0.5rem',
    background: colorTheme.palette.backgroundGrey200.main,
    '@media (max-width: 767.98px)': {
      textAlign: 'center',
      width: '100%',
      padding: '1.063rem 0 1.125rem',
    },
  },
  usagePolicy: {
    textDecoration: 'none',
    padding: '0.5rem 1rem',
    borderRadius: '0.5rem',
    background: colorTheme.palette.backgroundGrey200.main,
    '@media (max-width: 767.98px)': {
      textAlign: 'center',
      width: '100%',
      padding: '1.063rem 0 1.125rem',
    },
  },
};
