// import NotchCameraImage from '../../../assets/img/AboutVilna/Notch&Camera.svg';

export const notchStyles = {
  notch: {
    position: 'relative',
    bottom: '3px',
    left: '1px',
    height: '18px',
    width: '94px',
    margin: '0 auto',
    background: '#1A1C1E',
    borderBottomLeftRadius: '6px',
    borderBottomRightRadius: '6px',
    zIndex: '11',
    '&:before': {
      content: '" "',
      position: 'absolute',
      top: '20%',
      left: '50%',
      height: '6px',
      width: '6px',
      border: '1px solid #101417',
      borderRadius: '100%',
      transform: 'translate(-50%,-50%)',
      backgroundColor: '#080805',
    },
    '&:after': {
      content: '" "',
      position: 'absolute',
      top: '20%',
      left: '50%',
      height: '3px',
      width: '3px',
      backdropFilter: 'blur(5px)',
      borderRadius: '100%',
      transform: 'translate(-50%,-50%)',
      backgroundColor: '#0e314c',
    },

    '@media (max-width: 1023.98px)': {
      display: 'none',
    },
    '@media (max-width: 639.98px)': {
      display: 'inline-block',
      position: 'relative',
      top: '0',
      left: '22%',

      background: '#000',
      height: '17px',
      width: '108px',
      margin: '0 auto',
      borderBottomLeftRadius: '26px',
      borderBottomRightRadius: '26px',
      zIndex: '11',
      '&:before': {
        content: '" "',
        position: 'absolute',
        top: '20%',
        left: '50%',
        width: '24px',
        height: '4px',
        borderRadius: '5px',
        backgroundColor: '#0c0b0e',
        transform: 'translate(-50%,-50%)',
      },
      '&:after': {
        content: '" "',
        position: 'absolute',
        top: '20%',
        left: '70%',
        width: '8px',
        height: '8px',
        borderRadius: '5px',
        backgroundColor: '#0f0b25',
      },
    },
  },
};
