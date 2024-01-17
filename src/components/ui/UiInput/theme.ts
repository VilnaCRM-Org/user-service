import { createTheme } from '@mui/material';

export const theme = createTheme({
  components: {
    MuiTextField: {
      styleOverrides: {
        root: {
          '.MuiFormHelperText-root.Mui-error': {
            color: '#DC3939',
            paddingTop: '4px',
            fontSize: '14px',
            fontStyle: 'normal',
            fontWeight: '500',
            lineHeight: '18px',
            fontFamily: 'Inter',
            margin: '0',
            letterSpacing: '0',
          },
          fieldSet: {
            borderRadius: '8px',
            '&:hover': {
              border: '1px solid #D0D4D8',
            },
          },
          input: {
            boxSizing: 'border-box',
            padding: '0 28px',
            height: '64px',
            borderRadius: '8px',
            background: ' #FFF',
            '&::placeholder': {
              color: '#969B9D',
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
              backgroundColor: '#E1E7EA',
              color: '#969B9D',
            },
          },
        },
      },
    },
  },
});
