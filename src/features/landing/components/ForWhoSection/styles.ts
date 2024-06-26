import breakpointsTheme from '@/components/UiBreakpoints';
import colorTheme from '@/components/UiColorTheme';

export default {
  wrapper: {
    background: colorTheme.palette.backgroundGrey100.main,
    maxWidth: '100dvw',
    overflow: 'hidden',
  },

  lgCardsWrapper: {
    display: 'flex',
    [`@media (max-width: 968px)`]: {
      display: 'none',
    },
  },

  smCardsWrapper: {
    display: 'none',
    [`@media (max-width: 968px)`]: {
      display: 'flex',
      justifyContent: 'center',
    },
  },

  content: {
    pt: '8.25rem',
    position: 'relative',
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.lg}px)`]: {
      pt: '7.375rem',
    },
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.sm}px)`]: {
      pt: '2rem',
    },
  },

  mainImage: {
    img: {
      width: '100dvw',
      maxWidth: '51.4rem',
      height: '42.5rem',
      zIndex: '1',
      position: 'absolute',
      top: '7%',
      right: '-6%',
      '@media (max-width: 1130.98px)': {
        width: '100dvw',
        maxWidth: '43rem',
        height: '39.8rem',
        top: '5%',
        right: '-2.9%',
      },
      [`@media (max-width: 968px)`]: {
        maxWidth: '43.75rem',
        height: '39rem',
        top: '47%',
        right: '8%',
      },
      [`@media (max-width: ${breakpointsTheme.breakpoints.values.md}px)`]: {
        right: '-10%',
      },
      [`@media (max-width: ${breakpointsTheme.breakpoints.values.sm}px)`]: {
        height: '28rem',
        top: '86.5%',
        width: '29.5rem',
      },
      '@media (max-width: 475.98px)': {
        height: '24.688rem',
        right: '-18%',
        width: '26.5rem',
        top: '110%',
      },
      '@media (max-width: 425.98px)': {
        height: '24.688rem',
        right: '-14%',
        width: '27.5rem',
        top: '100%',
      },
      [`@media (max-width: ${breakpointsTheme.breakpoints.values.xs}px)`]: {
        right: '-32.5%',
        top: '110%',
      },
    },
  },

  line: {
    position: 'relative',
    background: colorTheme.palette.white.main,
    minHeight: '6.25rem',
    zIndex: 1,
    marginTop: '-3.75rem',
    '@media (max-width: 1130.98px)': {
      minHeight: '11.188rem',
      marginTop: '-8.625rem',
    },
    [`@media (max-width: 968px)`]: {
      display: 'none',
    },
  },
};
