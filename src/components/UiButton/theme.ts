import { Interpolation, Theme, createTheme } from '@mui/material';

import { colorTheme } from '../UiColorTheme';

export const repeatStyles: Interpolation<{ theme: Theme }> = {
  textTransform: 'none',
  textDecoration: 'none',
  fontSize: '0.938rem',
  fontWeight: '500',
  fontFamily: 'Golos',
  lineHeight: '1.125',
  letterSpacing: '0',
};

export const theme = createTheme({
  components: {
    MuiButton: {
      variants: [
        {
          props: {
            variant: 'contained',
            size: 'small',
          },
          style: {
            ...repeatStyles,
            backgroundColor: colorTheme.palette.primary.main,
            borderRadius: '3.563rem',
            padding: '16px 24px',
            '&:hover': {
              backgroundColor: '#00A3FF',
            },
            '&:active': {
              backgroundColor: ' #0399ED',
            },
            '&:disabled': {
              backgroundColor: colorTheme.palette.brandGray.main,
              color: colorTheme.palette.white.main,
            },
          },
        },
        {
          props: {
            variant: 'contained',
            size: 'medium',
          },
          style: {
            textTransform: 'none',
            textDecoration: 'none',
            borderRadius: '3.563rem',
            backgroundColor: colorTheme.palette.primary.main,
            padding: '1.25rem 2rem',
            alignSelf: 'center',
            fontFamily: 'Golos',
            fontWeight: '600',
            fontSize: '18px',
            letterSpacing: '0',
            lineHeight: 'normal',
            '&:hover': {
              backgroundColor: ' #00A3FF',
            },
            '&:active': {
              backgroundColor: ' #0399ED',
            },
            '&:disabled': {
              backgroundColor: colorTheme.palette.brandGray.main,
              color: colorTheme.palette.white.main,
            },
            '@media (max-width: 639.98px)': {
              fontSize: '15px',
              fontWeight: '400',
              lineHeight: '18px',
              padding: '16px 23px',
            },
          },
        },
        {
          props: {
            variant: 'outlined',
            size: 'small',
          },
          style: {
            ...repeatStyles,
            color: colorTheme.palette.darkSecondary.main,
            padding: '16px 23px',
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
          },
        },
      ],
    },
  },
});
