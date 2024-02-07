import breakpointsTheme from '@/components/UiBreakpoints';

import VectorIcon from '../../assets/svg/about-vilna/Vector.svg';

export default {
  vector: {
    backgroundImage: `url(${VectorIcon.src})`,
    backgroundSize: 'contain',
    backgroundRepeat: 'no-repeat',
    width: '100dvw',
    height: '100%',
    zIndex: '-2',
    position: 'absolute',
    left: '50%',
    top: '8%',
    transform: 'translateX(-50%)',
    '@media (max-width: 1440.98px)': {
      top: '5.4%',
    },
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.lg}px)`]: {
      top: '9%',
    },
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.sm}px)`]: {
      left: '39%',
      top: '16.4%',
      width: '35.125rem',
    },
  },
};
