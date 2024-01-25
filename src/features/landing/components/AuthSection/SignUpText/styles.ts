import { colorTheme } from '@/components/UiColorTheme';

export default {
  title: {
    paddingBottom: '40px',
    whiteSpace: 'pre-line',
    '@media (max-width: 1439.98px)': {
      textAlign: 'center',
      maxWidth: '683px',
      paddingBottom: '32px',
    },
    '@media (max-width: 639.98px)': {
      fontSize: '28px',
      textAlign: 'left',
      paddingBottom: '20px',
    },
  },
  titleVilnaCRM: {
    color: colorTheme.palette.primary.main,
    '@media (max-width: 639.98px)': {
      fontSize: '28px',
      textAlign: 'left',
    },
  },
  textWrapper: {
    pt: '136px',
    width: '50%',
    maxWidth: '561px',
    '@media (max-width: 1439.98px)': {
      maxWidth: '100%',
      display: 'flex',
      flexDirection: 'column',
      alignItems: 'center',
      width: '100%',
      pt: '56px',

      '@media (max-width: 639.98px)': {
        pt: '26px',
      },
    },
  },

  signInText: {
    mb: '24px',
    textAlign: 'left',
    '@media (max-width: 1439.98px)': {
      textAlign: 'center',
    },
    '@media (max-width: 639.98px)': {
      fontSize: '18px',
      alignSelf: 'start',
      mb: '22px',
    },
  },
};
