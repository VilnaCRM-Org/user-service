import PhoneMainImage from '../../../assets/img/AboutVilna/iphone_picture.png';

export const deviceImageStyles = {
  wrapper: {
    position: 'relative',
    overflow: 'hidden',
    width: '100%',
  },
  backgroundImage: {
    position: 'absolute',
    background:
      'linear-gradient( to bottom, rgba(34, 181, 252, 1) 0%, rgba(252, 231, 104, 1) 100%)',
    width: '100%',
    maxwidth: '1192px',
    height: '493px',
    zIndex: '-1',
    top: '9%',
    left: '0',
    borderRadius: '48px',
    '@media (max-width: 639.98px)': {
      borderRadius: '24px',
      height: '284px',
      top: '11%',
    },
  },
  screenBorder: {
    maxWidth: '803.758px',
    border: '3px solid #78797D',
    borderBottom: '10px solid #252525',
    borderTopRightRadius: '30px',
    borderTopLeftRadius: '30px',
    overflow: 'hidden',
    '@media (max-width: 639.98px)': {
      backgroundImage: `url(${PhoneMainImage.src})`,
      backgroundSize: 'contain',
      backgroundRepeat: 'no-repeat',
      marginBottom: '-140px',
      width: '226.875px',
      height: '458.74px',
    },
  },
  screenBackground: {
    border: '4px solid #232122',
    borderTopRightRadius: '25px',
    borderTopLeftRadius: '25px',
    backgroundColor: '#1A1C1E',
    padding: '12px',
    overflow: 'hidden',
  },
};
