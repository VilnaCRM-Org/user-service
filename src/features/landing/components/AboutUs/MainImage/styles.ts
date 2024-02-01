import PhoneMainImage from '../../../assets/img/about-vilna/mobileBackground.webp';
import MainImageSrc from '../../../assets/img/about-vilna/Screen.webp';

export default {
  mainImageWrapper: {
    overflow: 'hidden',
    borderTopRightRadius: '0.625rem',
    borderTopLeftRadius: '0.625rem',
    marginTop: '-1.125rem',
    height: '31.125rem',
    width: '47.875rem',
    '@media (max-width: 1023.98px)': {
      borderRadius: '0.625rem',
      width: '28.125rem',
      height: '32.813rem',
      marginTop: '0',
    },
    '@media (max-width: 639.98px)': {
      width: '12.75rem',
      height: '27.25rem',
      marginTop: '-1.125rem',
    },
  },
  mainImage: {
    backgroundImage: `url(${MainImageSrc.src})`,
    borderRadius: '0.625rem',
    width: '47.875rem',
    height: '31.125rem',

    '@media (max-width: 1023.98px)': {
      backgroundSize: 'cover',
      backgroundRepeat: 'no-repeat',
      width: '100%',
      borderRadius: '1rem',
      height: '100%',
    },
    '@media (max-width: 639.98px)': {
      backgroundImage: `url(${PhoneMainImage.src})`,
      backgroundSize: 'cover',
      backgroundRepeat: 'no-repeat',
      width: '12.688rem',
      height: '19.75rem',
      borderTopRightRadius: '1.625rem',
      borderTopLeftRadius: '1.625rem',
      borderBottomLeftRadius: '0',
      borderBottomRightRadius: '0',
    },
  },
};
