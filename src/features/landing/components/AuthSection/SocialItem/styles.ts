import { colorTheme } from '@/components/UiColorTheme';

export const socialItemStyles = {
  itemWrapper: {
    width: '189px',
    py: '17px',
    borderRadius: '12px',
    border: `1px solid ${colorTheme.palette.brandGray.main}`,
    background: colorTheme.palette.white.main,
    '@media (max-width: 639.98px)': {
      width: '169px',
    },
  },
};
