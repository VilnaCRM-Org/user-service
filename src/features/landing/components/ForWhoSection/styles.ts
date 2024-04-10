import breakpointsTheme from '@/components/UiBreakpoints';
import colorTheme from '@/components/UiColorTheme';

import DesktopImageSrc from '../../assets/img/about-vilna/desktop.jpg';
import PhoneImageSrc from '../../assets/img/about-vilna/mobile.jpg';
import TabletImageSrc from '../../assets/img/about-vilna/tablet.jpg';

export default {
  wrapper: {
    background: colorTheme.palette.backgroundGrey100.main,
    maxWidth: '100dvw',
    overflow: 'hidden',
  },

  lgCardsWrapper: {
    display: 'flex',
    [`@media (max-width: 968px)`]: {
      display: 'none',
    },
  },

  smCardsWrapper: {
    display: 'none',
    [`@media (max-width: 968px)`]: {
      display: 'flex',
      justifyContent: 'center',
    },
  },

  content: {
    pt: '8.25rem',
    position: 'relative',
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.lg}px)`]: {
      pt: '7.375rem',
    },
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.sm}px)`]: {
      pt: '2rem',
    },
  },

  mainImage: {
    backgroundImage: `url(${DesktopImageSrc.src})`,
    backgroundSize: 'contain',
    backgroundRepeat: 'no-repeat',
    backgroundPosition: 'center',
    width: '100dvw',
    maxWidth: '51.875rem',
    height: '35rem',
    zIndex: '1',
    position: 'absolute',
    top: '12%',
    right: '-4%',
    '@media (max-width: 1160.98px)': {
      backgroundImage: `url(${TabletImageSrc.src})`,
      width: '100dvw',
      maxWidth: '47.5rem',
      height: '41.438rem',
      top: '6%',
      right: '-9%',
    },
    [`@media (max-width: 968px)`]: {
      maxWidth: '43.75rem',
      height: '39rem',
      top: '60%',
      right: '8%',
    },
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.md}px)`]: {
      right: '-10%',
    },
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.sm}px)`]: {
      height: '35rem',
      right: '-6%',
      top: '110%',
    },
    '@media (max-width: 425.98px)': {
      backgroundImage: `url(${PhoneImageSrc.src})`,
      height: '21rem',
      right: '-8%',
      top: '106.5%',
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
    [`@media (max-width: 968px)`]: {
      display: 'none',
    },
  },
};
