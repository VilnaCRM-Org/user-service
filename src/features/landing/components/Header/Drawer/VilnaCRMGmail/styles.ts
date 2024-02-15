import colorTheme from '@/components/UiColorTheme';

export default {
  gmailWrapper: {
    border: `1px solid ${colorTheme.palette.brandGray.main}`,
    py: '1.125rem',
    borderRadius: '0.5rem',
    mt: '0.375rem',
    maxHeight: '64px',
  },
  at: {
    fontWeight: 600,
    fontSize: '22px',
    mb: '2px',
  },
  gmailText: {
    color: colorTheme.palette.darkSecondary.main,
  },
};
