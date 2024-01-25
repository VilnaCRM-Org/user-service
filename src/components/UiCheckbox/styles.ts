import Check from '../../features/landing/assets/svg/checkbox/check.svg';
import { colorTheme } from '../UiColorTheme';

export default {
  checkboxWrapper: {
    display: 'grid',
    marginRight: '13px',
    padding: '0',
    input: {
      WebkitAppearance: 'none',
      appearance: 'none',
      width: '24px',
      height: '24px',
      borderRadius: '8px',
      border: `1px solid ${colorTheme.palette.grey400.main}`,
      background: '#fff',
      '&:hover': {
        cursor: 'pointer',
        border: '1px solid #1eaeff',
      },
      '&:checked': {
        backgroundColor: '#1eaeff',
        border: 'none',
        backgroundImage: `url(${Check.src})`,
        backgroundPosition: 'center center',
        backgroundRepeat: 'no-repeat',
      },
      '&:disabled': {
        cursor: 'default',
        backgroundColor: '#eaecee',
        border: 'none',
      },
    },
  },
};
