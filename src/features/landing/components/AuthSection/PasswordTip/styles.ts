import breakpointsTheme from '@/components/UiBreakpoints';
import colorTheme from '@/components/UiColorTheme';

export default {
  wrapper: {
    maxWidth: '12rem',
    margin: '-8px',
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.sm}px)`]: {
      margin: '0',
      maxWidth: '9rem',
    },
  },
  line: {
    pb: '2px',
    borderBottom: `2px solid ${colorTheme.palette.grey300.main}`,
  },
  recommendationText: {
    fontWeight: 'bold',
  },
  optionText: {
    fontSize: '0.775rem',
  },
};
