import PhoneMainImage from '../../../assets/img/about-vilna/mobileBackground.png';
import MainImageSrc from '../../../assets/img/about-vilna/Screen.png';

export default {
  mainImageWrapper: {
    overflow: 'hidden',
    borderTopRightRadius: '10px',
    borderTopLeftRadius: '10px',
    marginTop: '-18px',
    height: '498px',
    width: '766px',
    '@media (max-width: 1023.98px)': {
      borderRadius: '10px',
      width: '450px',
      height: '525px',
      marginTop: '0',
    },
    '@media (max-width: 639.98px)': {
      width: '204px',
      height: '436.443px',
      marginTop: '-18px',
    },
  },
  mainImage: {
    backgroundImage: `url(${MainImageSrc.src})`,
    borderRadius: '10px',
    width: '766px',
    height: '498px',

    '@media (max-width: 1023.98px)': {
      backgroundSize: 'cover',
      backgroundRepeat: 'no-repeat',
      width: '100%',
      borderRadius: '16px',
      height: '100%',
    },
    '@media (max-width: 639.98px)': {
      backgroundImage: `url(${PhoneMainImage.src})`,
      backgroundSize: 'cover',
      backgroundRepeat: 'no-repeat',
      width: '203px',
      height: '316px',
      borderTopRightRadius: '26px',
      borderTopLeftRadius: '26px',
      borderBottomLeftRadius: '0',
      borderBottomRightRadius: '0',
    },
  },
};
