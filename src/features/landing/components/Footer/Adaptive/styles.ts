import { colorTheme } from '@/components/UiColorTheme';

export default {
  wrapper: {
    marginBottom: '1.25rem',
    borderTop: `1px solid  ${colorTheme.palette.brandGray.main}`,
    background: colorTheme.palette.white.main,
    boxShadow:
      ' 0px -5px 46px 0px rgba(198, 209, 220, 0.25), 0px -5px 46px 0px rgba(198, 209, 220, 0.25)',
  },
  gmailText: {
    color: colorTheme.palette.darkSecondary.main,
    textAlign: 'center',
    width: '100%',
    '@media (max-width: 767.98px)': {
      fontSize: '1.125rem',
      fontStyle: 'normal',
      fontWeight: '600',
      lineHeight: 'normal',
    },
  },
  gmailWrapper: {
    padding: '0.5rem 1rem',
    borderRadius: '0.5rem',
    background: colorTheme.palette.white.main,
    border: `1px solid ${colorTheme.palette.grey400.main}`,
    '@media (max-width: 767.98px)': {
      padding: '0.875rem 0 0.9375rem',
    },
  },
  copyright: {
    paddingBottom: '1.25rem',
    color: colorTheme.palette.grey200.main,
    textAlign: 'center',
    width: '100%',
    mt: '1rem',
  },
};
