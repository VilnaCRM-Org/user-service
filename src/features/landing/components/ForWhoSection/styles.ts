import { colorTheme } from '@/components/UiColorTheme';

import VectorIcon from '../../assets/svg/for-who/bg-lg.svg';
import VectorIconMd from '../../assets/svg/for-who/bg-md.svg';

export const forWhoSectionStyles = {
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
    pt: '132px',
    position: 'relative',
    '@media (max-width: 1439.98px)': {
      pt: '118px',
    },
    '@media (max-width: 639.98px)': {
      pt: '32px',
    },
  },

  mainImage: {
    backgroundImage: `url(${VectorIcon.src})`,
    backgroundSize: 'contain',
    backgroundRepeat: 'no-repeat',
    backgroundPosition: 'center',
    width: '100dvw',
    maxWidth: '830px',
    height: '715px',
    zIndex: '1',
    position: 'absolute',
    top: '15.8%',
    right: '-6.4%',
    '@media (max-width: 1130.98px)': {
      backgroundImage: `url(${VectorIconMd.src})`,
      width: '100dvw',
      maxWidth: '760px',
      height: '663px',
      top: '5.8%',
      right: '-9%',
    },
    '@media (max-width: 1023.98px)': {
      maxWidth: '700px',
      top: '46.5%',
      right: '-8%',
    },
    '@media (max-width: 425.98px)': {
      right: '-28%',
      width: '424px',
    },
  },
  line: {
    position: 'relative',
    background: colorTheme.palette.white.main,
    minHeight: '100px',
    zIndex: 1,
    marginTop: '-60px',
    '@media (max-width: 1130.98px)': {
      minHeight: '179px',
      marginTop: '-138px',
    },
    '@media (max-width: 1023.98px)': {
      display: 'none',
    },
  },
};