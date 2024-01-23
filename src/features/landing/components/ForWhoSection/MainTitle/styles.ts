import { colorTheme } from '@/components/UiColorTheme';

export const mainTitleStyles = {
  title: {
    '@media (max-width: 1023.98px)': {
      color: colorTheme.palette.darkPrimary.main,
      fontFamily: 'Golos',
      fontSize: '28px',
      fontStyle: 'normal',
      fontHeight: '700',
      lineHeight: 'normal',
    },
  },
  description: {
    pt: '16px',
    pb: '24px',
    '@media (max-width: 1130.98px)': {
      pb: '32px',
      maxWidth: '303px',
    },
    '@media (max-width: 1023.98px)': {
      pt: '13px',
      color: colorTheme.palette.darkPrimary.main,
      fontfamily: 'Golos',
      fontSize: '15px',
      fontStyle: 'normal',
      fontSeight: '400',
      lineHeight: '25px',
      pb: '0',
      maxWidth: '100%',
      paddingBottom: '174px',
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
