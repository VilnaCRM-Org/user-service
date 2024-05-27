import { Interpolation, Theme, createTheme } from '@mui/material';

import { golos } from '@/config/Fonts/golos';

import breakpointsTheme from '../UiBreakpoints';
import colorTheme from '../UiColorTheme';

export const containedStyles: Interpolation<{ theme: Theme }> = {
  textTransform: 'none',
  textDecoration: 'none',
  fontSize: '0.938rem',
  fontFamily: golos.style.fontFamily,
  fontWeight: '500',
  lineHeight: '1.125',
  letterSpacing: '0',
  backgroundColor: colorTheme.palette.primary.main,
  borderRadius: '3.563rem',
  '&:hover': {
    backgroundColor: colorTheme.palette.containedButtonHover.main,
  },
  '&:active': {
    backgroundColor: colorTheme.palette.containedButtonActive.main,
  },
  '&:disabled': {
    backgroundColor: colorTheme.palette.brandGray.main,
    color: colorTheme.palette.white.main,
  },
};

export const outlinedStyles: Interpolation<{ theme: Theme }> = {
  textTransform: 'none',
  textDecoration: 'none',
  fontSize: '0.938rem',
  fontFamily: golos.style.fontFamily,
  fontWeight: '500',
  lineHeight: '1.125',
  letterSpacing: '0',
  color: colorTheme.palette.darkSecondary.main,
  backgroundColor: colorTheme.palette.white.main,
  border: `1px solid ${colorTheme.palette.grey300.main}`,
  borderRadius: '3.563rem',
  '&:hover': {
    backgroundColor: colorTheme.palette.grey500.main,
    border: '1px solid rgba(0,0,0,0)',
  },
  '&:active': {
    border: `1px solid ${colorTheme.palette.grey500.main}`,
  },
  '&:disabled': {
    backgroundColor: colorTheme.palette.brandGray.main,
    color: colorTheme.palette.white.main,
    border: 'none',
  },
};

export const theme: Theme = createTheme({
  components: {
    MuiButton: {
      variants: [
        {
          props: { variant: 'contained', size: 'small' },
          style: { ...containedStyles, padding: '1rem 1.5rem' },
        },
        {
          props: { variant: 'contained', size: 'medium' },
          style: {
            ...containedStyles,
            alignSelf: 'center',
            fontWeight: '600',
            fontSize: '1.125rem',
            padding: '1.25rem 2rem',
            [`@media (max-width: ${breakpointsTheme.breakpoints.values.sm}px)`]: {
              fontSize: '0.9375rem',
              fontWeight: '400',
              lineHeight: '1.125rem',
              padding: '1rem 1.438rem',
            },
          },
        },
        {
          props: { variant: 'outlined', size: 'small' },
          style: { ...outlinedStyles, padding: '1rem 1.438rem' },
        },
        {
          props: { variant: 'outlined', size: 'medium' },
          style: {
            ...outlinedStyles,
            fontWeight: '600',
            fontSize: '1.125rem',
            padding: '1.25rem 2rem',
          },
        },
        {
          props: {
            name: 'socialButton',
            variant: 'outlined',
            size: 'medium',
          },
          style: {
            fontFamily: golos.style.fontFamily,
            textTransform: 'none',
            borderRadius: '0.75rem',
            padding: '1.125rem',
            gap: '0.563rem',
            border: `1px solid ${colorTheme.palette.brandGray.main}`,
            background: colorTheme.palette.white.main,
            color: colorTheme.palette.grey200.main,
            '&:hover': {
              background: colorTheme.palette.white.main,
              boxShadow: '0px 4px 7px 0px rgba(116, 134, 151, 0.17)',
              border: `1px solid ${colorTheme.palette.brandGray.main}`,
            },
            '&:active': {
              background: colorTheme.palette.white.main,
              boxShadow: '0px 4px 7px 0px rgba(71, 85, 99, 0.21)',
              border: `1px solid ${colorTheme.palette.grey300.main}`,
            },
            '&:disabled': {
              background: colorTheme.palette.brandGray.main,
              boxShadoiw: 'none',
              border: 'none',
              img: {
                opacity: '0.2',
              },
              div: {
                color: colorTheme.palette.white.main,
              },
            },
          },
        },
      ],
    },
  },
});
