import { colorTheme } from '@/components/UiColorTheme';

import Imagess from '../../../assets/svg/auth-section/bg.svg';
import Images from '../../../assets/svg/auth-section/image.svg';

export default {
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
  backgroundBlock: {
    position: 'absolute',
    borderRadius: '12px 12px 0px 0px',
    top: '16.2%',
    right: '5%',
    width: '499px',
    height: ' 542px',
    backgroundColor: colorTheme.palette.brandGray.main,
    '@media (max-width: 1439.98px)': {
      height: ' 629px',
      top: '8.3%',
      right: '26%',
    },
    '@media (max-width: 1023.98px)': {
      top: '6.8%',
    },
    '@media (max-width: 639.98px)': {
      display: 'none',
    },
  },
  formTitle: {
    paddingBottom: '32px',
    '@media (max-width: 1439.98px)': {
      maxWidth: '357px',
      paddingBottom: '20px',
    },

    '@media (max-width: 639.98px)': {
      maxWidth: '245px',
      fontSize: '22px',
      fontStyle: 'normal',
      fontWeight: '700',
      lineHeight: 'normal',
      paddingBottom: '19px',
    },
  },
  formContent: {
    position: 'relative',
    zIndex: '5',
    padding: '36px 40px 40px 40px',
    borderRadius: '32px 32px 0px 0px',
    border: '1px solid  primary.main',
    background: colorTheme.palette.white.main,
    maxWidth: '502px',
    maxHeight: '647px',
    boxShadow: '1px 1px 41px 0px rgba(59, 68, 80, 0.05)',

    '@media (max-width: 1439.98px)': {
      padding: '40px 41px 56px 41px',
      minWidth: '636px',
      maxHeight: '686px',
    },

    '@media (max-width: 639.98px)': {
      minWidth: '100%',
      maxWidth: '345px',
      maxHeight: '512px',
      padding: '24px 24px 32px 24px',
    },
  },
  inputsWrapper: {
    flexDirection: 'column',
    gap: '22px',
    '@media (max-width: 1439.98px)': {
      gap: '15px',
    },
  },

  inputWrapper: {
    flexDirection: 'column',
    gap: '9px',
    position: 'relative',
    '@media (max-width: 1023.98px)': {
      gap: '5px',
    },
  },
  inputTitle: {
    '@media (max-width: 1439.98px)': {
      fontSize: '16px',
    },
    '@media (max-width: 1023.98px)': {
      fontSize: '14px',
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
    mt: '20px',
    mb: '32px',
    mx: '0px',
    '@media (max-width: 1439.98px)': {
      mb: '24px',
    },
    '@media (max-width: 639.98px)': {
      mb: '19px',
    },
  },

  button: { height: '100%' },
  privacyText: {
    letterSpacing: '0px',
    '@media (max-width: 1439.98px)': {
      fontSize: '16px',
      maxWidth: '413px',
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
      bottom: '16.5%',
      width: '815px',
      height: '552px',
    },

    '@media (max-width: 639.98px)': {
      display: 'none',
    },
  },
  errorText: {
    top: '100%',
    position: 'absolute',
    color: colorTheme.palette.error.main,
    '@media (max-width: 639.98px)': {
      fontSize: '12px',
    },
  },
};
