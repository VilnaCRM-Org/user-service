import { createTheme } from '@mui/material';

import { colorTheme } from '../UiColorTheme';

export const theme = createTheme({
  components: {
    MuiOutlinedInput: {
      styleOverrides: {
        root: {
          borderRadius: '0.5rem',
          '&:hover .MuiOutlinedInput-notchedOutline': {
            borderColor: colorTheme.palette.grey300.main,
          },
          '&.Mui-focused .MuiOutlinedInput-notchedOutline': {
            border: `1px solid ${colorTheme.palette.grey300.main}`,
          },
          '&.Mui-disabled': {
            backgroundColor: colorTheme.palette.brandGray.main,
            color: colorTheme.palette.grey300.main,
          },
        },
        notchedOutline: {
          border: `1px solid ${colorTheme.palette.grey400.main}`,
          borderRadius: '0.5rem',
          '&:hover': {
            borderColor: colorTheme.palette.grey300.main,
          },
        },
      },
    },

    MuiTextField: {
      styleOverrides: {
        root: {
          input: {
            padding: '0 1.75rem',
            height: '4rem',
            borderRadius: '0.5rem',
            background: colorTheme.palette.white.main,
            '&::placeholder': {
              color: colorTheme.palette.grey300.main,
              fontFamily: 'Inter',
              fontSize: '1rem',
              fontStyle: 'normal',
              fontWeight: '400',
              lineHeight: '1.125rem',
            },
            '@media (max-width: 1439.98px)': {
              height: '4.938rem',
              '&::placeholder': {
                fontSize: '1.125rem',
              },
            },
            '@media (max-width: 639.98px)': {
              padding: '0 1.25rem',
              height: '3rem',
              '&::placeholder': {
                fontSize: '0.875rem',
                fontWeight: '500',
                lineHeight: '1.125rem',
              },
            },
            '&.Mui-disabled': {
              backgroundColor: colorTheme.palette.brandGray.main,
              color: colorTheme.palette.grey300.main,
            },
          },
        },
      },
    },
  },
});
