import { colorTheme } from '@/components/UiColorTheme';

export default {
  textWrapper: {
    marginLeft: '-16px',
    '@media (max-width: 639.98px)': {
      marginLeft: '0',
    },
  },
  title: {
    mb: '7px',
    padding: '12px 32px',
    alignSelf: 'center',
    borderRadius: '1rem',
    backgroundColor: colorTheme.palette.secondary.main,
    '@media (max-width: 639.98px)': {
      padding: '12px 24px',
      alignSelf: 'start',
      fontSize: '22px',
      fontWeight: '700',
    },
  },
  text: {
    textAlign: 'center',
    '@media (max-width: 639.98px)': {
      textAlign: 'left',
      fontSize: '28px',
      fontWeight: '700',
    },
  },
};
