import { colorTheme } from '../UiColorTheme';

export default {
  hoveredCard: {
    color: colorTheme.palette.primary.main,
    textDecoration: 'underline',
    fontWeight: '700',
    '@media (max-width: 1439.98px)': {
      color: colorTheme.palette.darkPrimary.main,
      textDecoration: 'none',
      fontWeight: '400',
    },
  },
};
