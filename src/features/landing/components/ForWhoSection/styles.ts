import breakpointsTheme from '@/components/UiBreakpoints';
import colorTheme from '@/components/UiColorTheme';

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
      height: '41.438rem',
      top: '5.8%',
      right: '-9%',
    },
    [`@media (max-width: 968px)`]: {
      maxWidth: '43.75rem',
      height: '44.688rem',
      top: '40%',
      right: '8%',
    },
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.md}px)`]: {
      right: '-10%',
    },
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.sm}px)`]: {
      height: '38rem',
      right: '-6%',
      width: '29.5rem',
      top: '38.5%',
    },
    '@media (max-width: 425.98px)': {
      height: '44.688rem',
      right: '-28%',
      width: '26.5rem',
      top: '32.5%',
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
