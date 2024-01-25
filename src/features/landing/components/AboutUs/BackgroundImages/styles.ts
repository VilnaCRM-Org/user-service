import LargeVectorIcon from '../../../assets/svg/about-vilna/largeVector.svg';
import VectorIcon from '../../../assets/svg/about-vilna/Vector.svg';

export default {
  vector: {
    backgroundImage: `url(${LargeVectorIcon.src})`,
    backgroundSize: 'cover',
    backgroundRepeat: 'no-repeat',
    width: '100dvw',
    maxWidth: '107.938rem',
    height: '56.25rem',
    zIndex: '-2',
    position: 'absolute',
    left: '50%',
    top: '6%',
    transform: 'translateX(-50%)',
    '@media (max-width: 1440.98px)': {
      backgroundImage: `url(${VectorIcon.src})`,
      backgroundSize: 'contain',
      backgroundPosition: 'center',
      top: '11%',
    },
    '@media (max-width: 1024.98px)': {
      top: '5%',
    },
    '@media (max-width: 639.98px)': {
      left: '41%',
      top: '-4%',
      width: '33.125rem',
    },
  },
};
