import { colorTheme } from '@/components/UiColorTheme';

export default {
  wrapper: {
    position: 'relative',
    overflow: 'hidden',
    width: '100%',
  },
  backgroundImage: {
    position: 'absolute',
    background:
      'linear-gradient( to bottom, rgba(34, 181, 252, 1) 0%, rgba(252, 231, 104, 1) 100%)',
    width: '100%',
    maxwidth: '1192px',
    height: '493px',
    zIndex: '-1',
    top: '9%',
    left: '0',
    borderRadius: '48px',
    '@media (max-width: 1023.98px)': {
      top: '11%',
    },
    '@media (max-width: 639.98px)': {
      borderRadius: '24px',
      height: '284px',
      top: '14%',
    },
  },
  screenBorder: {
    maxWidth: '803.758px',
    border: '3px solid #78797D',
    borderBottom: '10px solid #252525',
    borderTopRightRadius: '30px',
    borderTopLeftRadius: '30px',
    overflow: 'hidden',
    '@media (max-width: 1023.98px)': {
      borderRadius: '30px',
      borderBottom: 'none',
      border: 'none',
    },
    '@media (max-width: 639.98px)': {
      border: 'none',
      borderBottom: 'none',
      marginBottom: '-130px',
    },
  },
  screenBackground: {
    border: '4px solid #232122',
    borderTopRightRadius: '25px',
    borderTopLeftRadius: '25px',
    backgroundColor: colorTheme.palette.darkPrimary.main,
    padding: '12px',
    overflow: 'hidden',
    '@media (max-width: 1023.98px)': {
      borderRadius: '25px',
    },
    '@media (max-width: 639.98px)': {
      padding: '6px',
      border: '4px solid #444',
      borderRadius: '36px',
      backgroundColor: colorTheme.palette.darkPrimary.main,
      margin: '0 auto',
      overflow: 'hidden',
    },
  },
};
