import breakpointsTheme from '@/components/UiBreakpoints';
import colorTheme from '@/components/UiColorTheme';

export default {
  wrapper: {
    height: '100%',
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.sm}px)`]: {
      height: '19.25rem',
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
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.sm}px)`]: {
      p: '1rem 1.125rem 4.5rem 1rem',
      borderRadius: '0.75rem',
      border: `1px solid ${colorTheme.palette.grey500.main}`,
      maxHeight: '16.438rem',
    },
  },
  title: {
    pt: '1rem',
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.lg}px)`]: {
      fontSize: '1.375rem',
    },
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.sm}px)`]: {
      pt: '1rem',
      fontSize: '1.125rem',
    },
  },
  text: {
    mt: '0.75rem',
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.sm}px)`]: {
      fontSize: '0.9375rem',
      lineHeight: '1.563rem',
    },
  },
  image: {
    width: '4.375rem',
    height: '4.375rem',
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.md}px)`]: {
      width: '3.125rem',
      height: '3.125rem',
    },
  },
};
