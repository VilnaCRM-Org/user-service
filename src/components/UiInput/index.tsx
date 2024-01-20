/* eslint-disable react/jsx-props-no-spreading */
import { ThemeProvider, TextField } from '@mui/material';
import { TextFieldProps } from '@mui/material/TextField';
import React from 'react';

import { theme } from './theme';

const UiInput = React.forwardRef<HTMLInputElement, TextFieldProps>(
  (props, ref) => (
    <ThemeProvider theme={theme}>
      <TextField ref={ref} {...props} />
    </ThemeProvider>
  )
);
UiInput.displayName = 'UiInput';
export default UiInput;
