import { colorTheme } from '@/components/UiColorTheme';

export default {
  footerWrapper: {
    borderTop: '1px solid #e1e7ea',
    background: colorTheme.palette.white.main,
    boxShadow: '0px -5px 46px 0px rgba(198, 209, 220, 0.25)',
  },
  copyrightAndLinksWrapper: {
    borderRadius: '16px 16px 0px 0px',
    background: colorTheme.palette.backgroundGrey200.main,
  },
  copyrightAndLinks: {
    height: '59px',
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    ml: '5px',
    pb: '2px',
    '@media (max-width: 1439.98px)': {
      pb: '3px',
      ml: '0',
    },
  },
};
