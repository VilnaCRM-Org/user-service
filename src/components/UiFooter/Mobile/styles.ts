import colorTheme from '@/components/UiColorTheme';

export default {
  wrapper: {
    marginBottom: '0.75rem',
    borderTop: `1px solid  ${colorTheme.palette.brandGray.main}`,
    background: colorTheme.palette.white.main,
    boxShadow:
      ' 0px -5px 46px 0px rgba(198, 209, 220, 0.25), 0px -5px 46px 0px rgba(198, 209, 220, 0.25)',
  },
  content: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginTop: '1.125rem',
    paddingBottom: '0.75rem',
    '@media (max-width: 350px)': {
      gap: '0.5rem',
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
