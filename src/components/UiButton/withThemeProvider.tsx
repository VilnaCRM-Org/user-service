import { ThemeProvider } from '@mui/material';

import { theme } from './theme';
import { UiButtonProps } from './types';

export default function withThemeProvider(
  Component: React.ComponentType<UiButtonProps>
) {
  return function WithThemeProvider(props: UiButtonProps): React.ReactElement {
    const {
      onClick,
      sx,
      href,
      type,
      variant,
      size,
      fullWidth,
      children,
      name,
    } = props as UiButtonProps;
    return (
      <ThemeProvider theme={theme}>
        <Component
          onClick={onClick}
          variant={variant}
          size={size}
          sx={sx}
          fullWidth={fullWidth}
          href={href}
          type={type}
          name={name}
        >
          {children}
        </Component>
      </ThemeProvider>
    );
  };
}
