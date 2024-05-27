import { TextField, ThemeProvider } from '@mui/material';
import React from 'react';

import { theme } from './theme';
import { UiInputProps } from './types';

const UiInput: React.ForwardRefExoticComponent<
  UiInputProps & React.RefAttributes<HTMLInputElement>
> = React.forwardRef<HTMLInputElement, UiInputProps>(
  (
    { sx, placeholder, error, onBlur, type, fullWidth, value, onChange, disabled, onInput, id },
    ref
  ) => (
    <ThemeProvider theme={theme}>
      <TextField
        sx={sx}
        placeholder={placeholder}
        inputRef={ref}
        error={error}
        type={type}
        onChange={onChange}
        onBlur={onBlur}
        value={value}
        fullWidth={fullWidth}
        disabled={disabled}
        onInput={onInput}
        id={id}
      />
    </ThemeProvider>
  )
);

UiInput.displayName = 'UiInput';

export default UiInput;
