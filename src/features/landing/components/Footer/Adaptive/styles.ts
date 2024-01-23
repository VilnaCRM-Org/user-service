import { colorTheme } from '@/components/UiColorTheme';

export const adaptiveStyles = {
  wrapper: {
    display: 'none',
    marginBottom: '20px',
    borderTop: `1px solid  ${colorTheme.palette.brandGray.main}`,
    background: colorTheme.palette.white.main,
    boxShadow:
      ' 0px -5px 46px 0px rgba(198, 209, 220, 0.25), 0px -5px 46px 0px rgba(198, 209, 220, 0.25)',
    '@media (max-width: 767.98px)': {
      display: 'inline-block',
    },
  },
  gmailText: {
    color: colorTheme.palette.darkSecondary.main,
    textAlign: 'center',
    width: '100%',
    '@media (max-width: 767.98px)': {
      fontFamily: 'Golos',
      fontSize: '18px',
      fontStyle: 'normal',
      fontWeight: '600',
      lineHeight: 'normal',
    },
  },
  gmailWrapper: {
    padding: '8px 16px',
    borderRadius: '8px',
    background: colorTheme.palette.white.main,
    border: `1px solid ${colorTheme.palette.grey400.main}`,
    '@media (max-width: 767.98px)': {
      padding: '14px 0 15px',
    },
  },
  copyright: {
    paddingBottom: '20px',
    color: colorTheme.palette.grey200.main,
    textAlign: 'center',
    width: '100%',
    mt: '16px',
  },
};
