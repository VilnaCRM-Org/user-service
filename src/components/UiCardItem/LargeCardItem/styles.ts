import { colorTheme } from '@/components/UiColorTheme';

export default {
  wrapper: {
    height: '100%',
    '@media (max-width: 639.98px)': {
      height: '308px',
    },
  },
  content: {
    height: '100%',
    p: '1.5rem',
    borderRadius: '0.75rem',
    border: `1px solid ${colorTheme.palette.grey500.main}`,
    ':hover': {
      boxShadow: '0px 8px 27px 0px rgba(49, 59, 67, 0.14)',
      border: `1px solid ${colorTheme.palette.grey400.main}`,
    },
    '@media (max-width: 639.98px)': {
      p: '16px 18px 72px 16px',
      borderRadius: '0.75rem',
      border: `1px solid ${colorTheme.palette.grey500.main}`,
      maxHeight: '263px',
    },
  },
  title: {
    pt: '16px',
    '@media (max-width: 1439.98px)': {
      fontSize: '22px',
    },
    '@media (max-width: 639.98px)': {
      pt: '16px',
      fontSize: '18px',
    },
  },
  text: {
    mt: '12px',
    '@media (max-width: 639.98px)': {
      fontSize: '15px',
      lineHeight: '25px',
    },
  },
  image: {
    width: '70px',
    height: '70px',
    '@media (max-width: 639.98px)': {
      width: '50px',
      height: '50px',
    },
  },
};
