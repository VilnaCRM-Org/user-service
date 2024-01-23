import { colorTheme } from '@/components/UiColorTheme';

export const privacyPolicyStyles = {
  wrapper: {
    gap: '8px',
    flexDirection: 'row',
    '@media (max-width: 1439.98px)': {
      marginRight: '-8px',
    },
    '@media (max-width: 767.98px)': {
      marginRight: '0',
      flexDirection: 'column',
      gap: '4px',
      pt: '4px',
    },
  },
  privacy: {
    padding: '8px 16px',
    borderRadius: '8px',
    background: colorTheme.palette.backgroundGrey200.main,
    '@media (max-width: 767.98px)': {
      textAlign: 'center',
      width: '100%',
      padding: '17px 0 18px',
    },
  },
  usagePolicy: {
    padding: '8px 16px',
    borderRadius: '8px',
    background: colorTheme.palette.backgroundGrey200.main,
    '@media (max-width: 767.98px)': {
      textAlign: 'center',
      width: '100%',
      padding: '17px 0 18px',
    },
  },
};
