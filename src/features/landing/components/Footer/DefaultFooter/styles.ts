import breakpointsTheme from '@/components/UiBreakpoints';
import colorTheme from '@/components/UiColorTheme';

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
    paddingLeft: '1rem',
    paddingRight: '1rem',
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.xl}px)`]: {
      paddingLeft: '2rem',
      paddingRight: '1.5rem',
    },
  },
  bottomWrapper: {
    width: '100%',
    maxWidth: '1222px',
    margin: '0 auto',
    paddingLeft: '1.3rem',
    paddingRight: '1rem',
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.xl}px)`]: {
      paddingRight: '2rem',
      paddingLeft: '2rem',
    },
  },

  copyrightAndLinksWrapper: {
    borderRadius: '1rem 1rem 0px 0px',
    background: colorTheme.palette.backgroundGrey200.main,
  },
  copyrightAndLinks: {
    height: '3.688rem',
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.xl}px)`]: {
      pb: '0.2rem',
    },
  },

  copyright: {
    color: colorTheme.palette.grey200.main,
  },
};
