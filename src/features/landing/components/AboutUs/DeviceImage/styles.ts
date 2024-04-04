import breakpointsTheme from '@/components/UiBreakpoints';
import colorTheme from '@/components/UiColorTheme';

export default {
  wrapper: {
    position: 'relative',
    overflow: 'hidden',
    width: '100%',
    zIndex: 2,
  },

  backgroundImage: {
    position: 'absolute',
    background:
      'linear-gradient( to bottom, rgba(34, 181, 252, 1) 0%, rgba(252, 231, 104, 1) 100%)',
    width: '100%',
    maxwidth: '74.5rem',
    height: '30.813rem',
    zIndex: '-1',
    top: '9%',
    left: '0',
    borderRadius: '3rem',
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.lg}px)`]: {
      top: '11%',
    },
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.sm}px)`]: {
      borderRadius: '1.5rem',
      height: '17.75rem',
      top: '14%',
    },
  },

  screenBorder: {
    maxWidth: '50.25rem',
    border: '3px solid #78797D',
    borderBottom: '0.625rem solid #252525',
    borderTopRightRadius: '1.875rem',
    borderTopLeftRadius: '1.875rem',
    overflow: 'hidden',
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.lg}px)`]: {
      borderRadius: '1.875rem',
      borderBottom: 'none',
      border: 'none',
    },
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.sm}px)`]: {
      border: 'none',
      borderBottom: 'none',
      marginBottom: '-8.125rem',
    },
  },

  screenBackground: {
    border: '4px solid #232122',
    borderTopRightRadius: '1.563rem',
    borderTopLeftRadius: '1.563rem',
    backgroundColor: colorTheme.palette.darkPrimary.main,
    padding: '0.75rem',
    overflow: 'hidden',
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.lg}px)`]: {
      borderRadius: '1.563rem',
    },
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.sm}px)`]: {
      padding: '0.375rem',
      border: '4px solid #444',
      borderRadius: '2.25rem',
      backgroundColor: colorTheme.palette.darkPrimary.main,
      margin: '0 auto',
      overflow: 'hidden',
    },
  },
};
