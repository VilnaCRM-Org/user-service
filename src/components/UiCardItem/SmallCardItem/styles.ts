import breakpointsTheme from '@/components/UiBreakpoints';
import colorTheme from '@/components/UiColorTheme';

export default {
  wrapper: {
    padding: '2.5rem 2rem 2.5rem 1.563rem',
    borderRadius: '0.75rem',
    border: `1px solid ${colorTheme.palette.grey500.main}`,
    maxHeight: '20.75rem',
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.xl}px)`]: {
      padding: '2.125rem 1.875rem 2.125rem 1.563rem',
      flexDirection: 'row',
      alignItems: 'center',
      gap: '2.813rem',
      maxHeight: '11.375rem',
    },
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.sm}px)`]: {
      flexDirection: 'column',
      padding: '1rem 1.125rem 2.5rem 1rem ',
      gap: '1rem',
      alignItems: 'start',
      minHeight: '15.125rem',
    },
  },

  title: {
    pt: '2rem',
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.xl}px)`]: {
      pt: '0',
    },
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.lg}px)`]: {
      fontSize: '1.125rem',
      fontWeight: '600',
    },
  },

  text: {
    mt: '0.625rem',
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.xl}px)`]: {
      a: {
        textDecoration: 'none',
        fontWeight: '400',
        color: colorTheme.palette.darkPrimary.main,
      },
    },
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.lg}px)`]: {
      fontSize: '0.9375rem',
      fontWeight: '400',
      lineHeight: '1.563rem',
      mt: '0.75rem',
    },
  },

  image: {
    width: '5rem',
    height: '5rem',
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.sm}px)`]: {
      width: '3.125rem',
      height: '3.125rem',
    },
  },
  hoveredCard: {
    color: colorTheme.palette.primary.main,
    textDecoration: 'underline',
    fontWeight: '700',
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.xl}px)`]: {
      color: colorTheme.palette.darkPrimary.main,
      textDecoration: 'none',
      fontWeight: '400',
    },
  },
};
