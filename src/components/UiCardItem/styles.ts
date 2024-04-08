import breakpointsTheme from '../UiBreakpoints';
import colorTheme from '../UiColorTheme';

export default {
  smallWrapper: {
    padding: '2.5rem 2rem 2.5rem 1.563rem',
    borderRadius: '0.75rem',
    border: `1px solid ${colorTheme.palette.grey500.main}`,
    maxHeight: '20.75rem',
    alignItems: 'start',
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.xl - 1}px)`]: {
      padding: '2.125rem 1.875rem 2.125rem 1.563rem',
      flexDirection: 'row',
      alignItems: 'center',
      gap: '2.813rem',
      maxHeight: '11.375rem',
    },
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.sm}px)`]: {
      flexDirection: 'column',
      padding: '1rem 1.125rem 0rem 1rem',
      gap: '1rem',
      alignItems: 'start',
      minHeight: '15.125rem',
    },
  },

  smallTitle: {
    pt: '2rem',
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.lg}px)`]: {
      pt: '0',
    },
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.md}px)`]: {
      fontSize: '1.125rem',
      fontWeight: '600',
    },
  },

  smallText: {
    mt: '0.625rem',
    zIndex: 2,
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.lg}px)`]: {
      a: {
        textDecoration: 'none',
        fontWeight: '400',
        color: colorTheme.palette.darkPrimary.main,
      },
    },
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.md}px)`]: {
      fontSize: '0.9375rem',
      fontWeight: '400',
      lineHeight: '1.563rem',
      mt: '0.75rem',
    },
  },

  smallImage: {
    width: '5rem',
    height: '5rem',
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.sm}px)`]: {
      width: '3.125rem',
      height: '3.125rem',
    },
  },

  hoveredCard: {
    cursor: 'pointer',
    color: colorTheme.palette.primary.main,
    textDecoration: 'underline',
    fontWeight: '700',
  },

  largeWrapper: {
    p: '1.5rem',
    borderRadius: '0.75rem',
    border: `1px solid ${colorTheme.palette.grey500.main}`,
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.sm}px)`]: {
      padding: '1rem 1.125rem 0 1rem',
      borderRadius: '0.75rem',
      border: `1px solid ${colorTheme.palette.grey500.main}`,
      minHeight: '16.438rem',
    },
    ':hover': {
      boxShadow: '0px 8px 27px 0px rgba(49, 59, 67, 0.14)',
      border: `1px solid ${colorTheme.palette.grey400.main}`,
    },
  },

  largeTitle: {
    pt: '1rem',
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.lg}px)`]: {
      fontSize: '1.375rem',
    },
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.sm}px)`]: {
      pt: '1rem',
      fontSize: '1.125rem',
    },
  },

  largeText: {
    mt: '0.75rem',
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.sm}px)`]: {
      fontSize: '0.9375rem',
      lineHeight: '1.563rem',
    },
  },

  largeImage: {
    width: '4.375rem',
    height: '4.375rem',
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.md}px)`]: {
      width: '3.125rem',
      height: '3.125rem',
    },
  },
};
