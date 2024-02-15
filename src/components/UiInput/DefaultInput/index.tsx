import { TextFieldProps, ThemeProvider } from '@mui/material';

import { theme } from './theme';

export default function defaultInput(
  Component: React.ComponentType<TextFieldProps>
) {
  return function DefaultInput(props: TextFieldProps): React.ReactElement {
    const {
      ref,
      sx,
      children,
      placeholder,
      error,
      onBlur,
      type,
      fullWidth,
      value,
      onChange,
    } = props;
    return (
      <ThemeProvider theme={theme}>
        <Component
          sx={sx}
          placeholder={placeholder}
          ref={ref}
          error={error}
          type={type}
          onChange={onChange}
          onBlur={onBlur}
          value={value}
          fullWidth={fullWidth}
        >
          {children}
        </Component>
      </ThemeProvider>
    );
  };
}
