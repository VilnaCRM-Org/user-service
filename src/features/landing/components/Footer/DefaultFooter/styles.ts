import { colorTheme } from '@/components/UiColorTheme';

export default {
  footerWrapper: {
    borderTop: '1px solid #e1e7ea',
    background: colorTheme.palette.white.main,
    boxShadow: '0px -5px 46px 0px rgba(198, 209, 220, 0.25)',
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
    ml: '0.313rem',
    pb: '0.125rem',
    '@media (max-width: 1439.98px)': {
      pb: '0.188rem',
      ml: '0',
    },
  },
};
