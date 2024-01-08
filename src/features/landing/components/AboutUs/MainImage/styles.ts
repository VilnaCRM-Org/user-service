import PhoneMainImage from '../../../assets/img/AboutVilna/mobileBackground.png';
import MainImageSrc from '../../../assets/img/AboutVilna/Screen.png';

export const mainImageStyles = {
  mainImageWrapper: {
    overflow: 'hidden',
    borderTopRightRadius: '10px',
    borderTopLeftRadius: '10px',
    marginTop: '-18px',
    '@media (max-width: 639.98px)': {},
  },
  mainImage: {
    backgroundImage: `url(${MainImageSrc.src})`,
    width: '766px',
    height: '498px',
    '@media (max-width: 639.98px)': {
      backgroundImage: `url(${PhoneMainImage.src})`,
      backgroundSize: 'contain',
      backgroundRepeat: 'no-repeat',
      width: '201.927px',
      height: '436.443px',
    },
  },
};
