import breakpointsTheme from '@/components/UiBreakpoints';

import VectorIcon from '../../assets/svg/about-vilna/Vector.svg';

export default {
  vector: {
    backgroundImage: `url(${VectorIcon.src})`,
    backgroundSize: 'contain',
    backgroundRepeat: 'no-repeat',
    width: '100dvw',
    height: '100%',
    zIndex: '1',
    position: 'absolute',
    left: '50%',
    top: '2.6%',
    transform: 'translateX(-50%)',
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.lg}px)`]: {
      top: '5%',
    },
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.md}px)`]: {
      top: '6%',
    },
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.sm}px)`]: {
      top: '10.4%',
    },
    '@media (max-width: 425px)': {
      top: '12.4%',
    },
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.xs}px)`]: {
      top: '13.4%',
    },
    '@media (max-width: 320px)': {
      top: '16.4%',
    },
  },
};
