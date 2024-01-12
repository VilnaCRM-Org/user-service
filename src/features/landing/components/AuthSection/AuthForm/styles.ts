import Imagess from '../../../assets/svg/auth-section/bg.svg';
import Images from '../../../assets/svg/auth-section/image.svg';

export const authFormStyles = {
  formWrapper: {
    position: 'relative',
    mt: '65px',

    '@media (max-width: 1439.98px)': {
      mt: '62px',
    },

    '@media (max-width: 639.98px)': {
      mt: '34px',
    },
  },

  formTitle: {
    marginBottom: '32px',

    '@media (max-width: 1439.98px)': {
      maxWidth: '357px',
      marginBottom: '20px',
    },

    '@media (max-width: 639.98px)': {
      maxWidth: '245px',
      fontSize: '22px',
      fontStyle: 'normal',
      fontWeight: '700',
      lineHeight: 'normal',
    },
  },

  formContent: {
    position: 'relative',
    zIndex: '5',
    padding: '36px 40px 40px 40px',
    borderRadius: '32px 32px 0px 0px',
    border: '1px solid  #E1E7EA',
    background: '#FFF',
    maxWidth: '502px',
    maxHeight: '647px',
    boxShadow: '1px 1px 41px 0px rgba(59, 68, 80, 0.05)',

    '@media (max-width: 1439.98px)': {
      padding: '40px 41px 56px 41px',
      maxWidth: '636px',
      maxHeight: '686px',
    },

    '@media (max-width: 639.98px)': {
      maxWidth: '345px',
      maxHeight: '512px',
      padding: '24px 24px 32px 24px',
    },
  },

  labelTitle: {
    mt: '22px',
    paddingBottom: '9px',
    '@media (max-width: 1439.98px)': { mt: '16px', fontSize: '16px' },
    '@media (max-width: 639.98px)': {
      fontSize: '14px',
      paddingBottom: '4px',
    },
  },

  buttonWrapper: {
    maxWidth: '175px',

    '@media (max-width: 1439.98px)': {
      height: '70px',
      maxWidth: '100%',
    },

    '@media (max-width: 639.98px)': {
      height: '50px',
      maxWidth: '100%',
    },
  },

  labelText: {
    pt: '20px',
    pb: '32px',
    mx: '0px',
  },

  button: { height: '100%' },
  privacyText: {
    letterSpacing: '0px',

    '@media (max-width: 1439.98px)': {
      fontSize: '16px',
    },
    '@media (max-width: 639.98px)': {
      fontSize: '14px',
    },
  },
  backgroundImage: {
    backgroundImage: `url(${Images.src})`,
    width: '784px',
    height: '656px',
    position: 'absolute',
    left: '-40%',
    bottom: '0%',
    zIndex: '1',

    '@media (max-width: 1439.98px)': {
      backgroundImage: `url(${Imagess.src})`,
      left: '-12%',
      bottom: '16%',
      width: '815px',
      height: '552px',
    },

    '@media (max-width: 639.98px)': {
      display: 'none',
    },
  },
};
