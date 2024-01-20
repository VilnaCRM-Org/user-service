import VectorIcon from '../../../assets/img/about-vilna/Vector.svg';

export const backgroundImagesStyles = {
  vector: {
    backgroundImage: `url(${VectorIcon.src})`,
    backgroundSize: 'contain',
    backgroundRepeat: 'no-repeat',
    backgroundPosition: 'center',
    width: '100dvw',
    height: '900px',
    zIndex: '-2',
    position: 'absolute',
    left: '50%',
    top: { md: '4%', xl: '11%' },
    transform: 'translateX(-50%)',
    '@media (max-width: 639.98px)': {
      left: '41%',
      top: '-4%',
      width: '530px',
    },
  },
};
