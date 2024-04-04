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
    top: '5.4%',
    transform: 'translateX(-50%)',
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.xl}px)`]: {
      top: '5.4%',
    },
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.lg}px)`]: {
      top: '22%',
    },

    [`@media (max-width: ${breakpointsTheme.breakpoints.values.xs}px)`]: {
      top: '30.4%',
    },
  },
};
