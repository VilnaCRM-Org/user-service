import Check from '../../features/landing/assets/svg/checkbox/check.svg';
import colorTheme from '../UiColorTheme';

export default {
  checkboxWrapper: {
    display: 'grid',
    marginRight: '0.813rem',
    padding: '0',
    input: {
      WebkitAppearance: 'none',
      appearance: 'none',
      width: '1.5rem',
      height: '1.5rem',
      borderRadius: '0.5rem',
      border: `1px solid ${colorTheme.palette.grey400.main}`,
      background: colorTheme.palette.white.main,
      '&:hover': {
        cursor: 'pointer',
        border: `1px solid ${colorTheme.palette.primary.main}`,
      },
      '&:checked': {
        backgroundColor: colorTheme.palette.primary.main,
        border: 'none',
        backgroundImage: `url(${Check.src})`,
        backgroundPosition: 'center center',
        backgroundRepeat: 'no-repeat',
      },
      '&:disabled': {
        cursor: 'default',
        backgroundColor: colorTheme.palette.grey500.main,
        border: 'none',
      },
    },
  },
  checkboxWrapperError: {
    display: 'grid',
    marginRight: '0.813rem',
    padding: '0',
    input: {
      WebkitAppearance: 'none',
      appearance: 'none',
      width: '1.5rem',
      height: '1.5rem',
      borderRadius: '0.5rem',
      border: `1px solid ${colorTheme.palette.error.main}`,
      background: colorTheme.palette.white.main,
      '&:checked': {
        backgroundColor: colorTheme.palette.primary.main,
        border: 'none',
        backgroundImage: `url(${Check.src})`,
        backgroundPosition: 'center center',
        backgroundRepeat: 'no-repeat',
      },
    },
  },
};
