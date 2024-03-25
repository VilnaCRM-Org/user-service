import breakpointsTheme from '../../UiBreakpoints';
import colorTheme from '../../UiColorTheme';

export default {
  footerWrapper: {
    borderTop: '1px solid #e1e7ea',
    background: colorTheme.palette.white.main,
    boxShadow: '0px -5px 46px 0px rgba(198, 209, 220, 0.25)',
  },

  topWrapper: {
    width: '100%',
    maxWidth: '1222px',
    margin: '0 auto',
  },

  topContent: {
    paddingLeft: '1rem',
    paddingRight: '1rem',
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.lg}px)`]: {
      paddingLeft: '2rem',
      paddingRight: '1.5rem',
    },
  },

  copyrightAndLinksWrapper: {
    width: '100%',
    maxWidth: '1222px',
    margin: '0 auto',
  },

  bottomWrapper: {
    borderRadius: '1rem 1rem 0px 0px',
    background: colorTheme.palette.backgroundGrey200.main,
  },

  copyrightAndLinks: {
    paddingLeft: '1.3rem',
    paddingRight: '1rem',
    height: '3.688rem',
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingBottom: '0.3rem',
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.lg}px)`]: {
      paddingRight: '2rem',
      paddingLeft: '2rem',
      pb: '0.2rem',
    },
  },

  copyright: {
    color: colorTheme.palette.grey200.main,
  },
};
