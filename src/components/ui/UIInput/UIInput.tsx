import { createTheme, ThemeProvider, TextField } from '@mui/material';
import React, { forwardRef } from 'react';

import { UIInputProps } from './UIInputType';

const theme = createTheme({
  components: {
    MuiTextField: {
      styleOverrides: {
        root: {
          div: {},
          fieldSet: {
            border: 'none',
            maxWidth: '460px',
          },
          input: {
            boxSizing: 'border-box',
            padding: '0 28px',
            height: '64px',
            borderRadius: '8px',
            border: '1px solid  #D0D4D8',
            background: ' #FFF',
            '&:hover': {
              border: '1px solid  #969B9D',
            },
            '&::placeholder': {
              color: '#969B9D',
              fontFamily: 'Inter',
              fontSize: '16px',
              fontDtyle: 'normal',
              fontWeight: '400',
              lineHeight: '18px',
            },
            '&:focus': {},
            '&:active': {},
          },
        },
      },
    },
  },
});

const UIInput = forwardRef<HTMLInputElement, UIInputProps>(
  ({ placeholder, hasError, ...rest }, ref) => (
    <ThemeProvider theme={theme}>
      <TextField
        ref={ref}
        placeholder={placeholder}
        sx={{
          width: '100%',
          border: hasError ? '1px solid red' : 'none',
          borderRadius: hasError ? '8px' : 'none',
        }}
        // eslint-disable-next-line react/jsx-props-no-spreading
        {...rest}
      />
    </ThemeProvider>
  )
);

UIInput.displayName = 'UIInput';
export default UIInput;
