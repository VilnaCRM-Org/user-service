import breakpointsTheme from '../UiBreakpoints';

export default {
  default: {
    display: 'block',
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.md}px)`]: {
      display: 'none',
    },
  },

  adaptive: {
    display: 'none',
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.md}px)`]: {
      display: 'block',
    },
  },
};
