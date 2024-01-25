import { colorTheme } from '@/components/UiColorTheme';

import VectorIcon from '../../assets/svg/for-who/bg-lg.svg';
import VectorIconMd from '../../assets/svg/for-who/bg-md.svg';

export default {
  wrapper: {
    background: colorTheme.palette.backgroundGrey100.main,
    maxWidth: '100dvw',
    overflow: 'hidden',
  },
  lgCardsWrapper: {
    display: 'flex',
    '@media (max-width: 1023.98px)': {
      display: 'none',
    },
  },

  smCardsWrapper: {
    display: 'none',
    '@media (max-width: 1023.98px)': {
      display: 'flex',
      justifyContent: 'center',
    },
  },
  content: {
    pt: '8.25rem',
    position: 'relative',
    '@media (max-width: 1439.98px)': {
      pt: '7.375rem',
    },
    '@media (max-width: 639.98px)': {
      pt: '2rem',
    },
  },

  mainImage: {
    backgroundImage: `url(${VectorIcon.src})`,
    backgroundSize: 'contain',
    backgroundRepeat: 'no-repeat',
    backgroundPosition: 'center',
    width: '100dvw',
    maxWidth: '51.875rem',
    height: '44.688rem',
    zIndex: '1',
    position: 'absolute',
    top: '15.8%',
    right: '-6.4%',
    '@media (max-width: 1130.98px)': {
      backgroundImage: `url(${VectorIconMd.src})`,
      width: '100dvw',
      maxWidth: '47.5rem',
      height: '41.438',
      top: '5.8%',
      right: '-9%',
    },
    '@media (max-width: 1023.98px)': {
      maxWidth: '43.75rem',
      top: '46.5%',
      right: '-8%',
    },
    '@media (max-width: 425.98px)': {
      right: '-28%',
      width: '26.5rem',
    },
  },
  line: {
    position: 'relative',
    background: colorTheme.palette.white.main,
    minHeight: '6.25rem',
    zIndex: 1,
    marginTop: '-3.75rem',
    '@media (max-width: 1130.98px)': {
      minHeight: '11.188rem',
      marginTop: '-8.625rem',
    },
    '@media (max-width: 1023.98px)': {
      display: 'none',
    },
  },
};
