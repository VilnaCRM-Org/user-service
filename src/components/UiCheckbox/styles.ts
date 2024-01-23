import Check from '../../features/landing/assets/svg/checkbox/check.svg';

export const checkboxStyles = {
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
      border: '1px solid #d0d4d8',
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
