import VectorIcon from '../../../assets/img/AboutVilna/Vector.svg';

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
  },
};
