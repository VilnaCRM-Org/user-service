/* eslint-disable react/jsx-props-no-spreading */
import {
  createTheme,
  ThemeProvider,
  TextField,
  TextFieldVariants,
  TextFieldProps,
} from '@mui/material';
import React from 'react';

const theme = createTheme({
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
            margin: '0',
          },
          fieldSet: {
            border: 'none',
            maxWidth: '460px',
          },
          input: {
            border: '1px solid #D0D4D8',
            boxSizing: 'border-box',
            padding: '0 28px',
            height: '64px',
            borderRadius: '8px',
            background: ' #FFF',
            '&::placeholder': {
              color: '#969B9D',
              fontFamily: 'Inter',
              fontSize: '16px',
              fontDtyle: 'normal',
              fontWeight: '400',
              lineHeight: '18px',
            },
          },
        },
      },
    },
  },
});

const UiInput = React.forwardRef<
  HTMLInputElement,
  {
    variant?: TextFieldVariants;
  } & Omit<TextFieldProps, 'variant'>
>((props, ref) => (
  <ThemeProvider theme={theme}>
    <TextField
      ref={ref}
      // eslint-disable-next-line react/jsx-props-no-spreading
      {...props}
    />
  </ThemeProvider>
));

UiInput.displayName = 'UiInput';
UiInput.defaultProps = {
  variant: 'outlined',
};

export default UiInput;
