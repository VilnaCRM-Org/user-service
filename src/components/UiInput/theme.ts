import { createTheme } from '@mui/material';

import { colorTheme } from '../UiColorTheme';

export const theme = createTheme({
  components: {
    MuiOutlinedInput: {
      styleOverrides: {
        root: {
          borderRadius: '8px',
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
          borderRadius: '8px',
          '&:hover': {
            borderColor: colorTheme.palette.grey300.main,
          },
        },
      },
    },

    MuiTextField: {
      styleOverrides: {
        root: {
          '.MuiFormHelperText-root.Mui-error': {
            color: colorTheme.palette.error.main,
            paddingTop: '4px',
            fontSize: '14px',
            fontStyle: 'normal',
            fontWeight: '500',
            lineHeight: '18px',
            fontFamily: 'Inter',
            margin: '0',
            letterSpacing: '0',
          },
          input: {
            padding: '0 28px',
            height: '64px',
            borderRadius: '8px',
            background: colorTheme.palette.white.main,
            '&::placeholder': {
              color: colorTheme.palette.grey300.main,
              fontFamily: 'Inter',
              fontSize: '16px',
              fontStyle: 'normal',
              fontWeight: '400',
              lineHeight: '18px',
            },
            '@media (max-width: 1439.98px)': {
              height: '79px',
              '&::placeholder': {
                fontSize: '18px',
              },
            },
            '@media (max-width: 639.98px)': {
              padding: '0 20px',
              height: '48px',
              '&::placeholder': {
                fontSize: '14px',
                fontWeight: '500',
                lineHeight: '18px',
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
