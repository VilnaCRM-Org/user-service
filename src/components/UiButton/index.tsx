import { Button, ThemeProvider } from '@mui/material';

import { theme } from './theme';
import { UiButtonProps } from './types';

function UiButton({
  variant,
  size,
  disabled,
  fullWidth,
  onClick,
  type,
  children,
  sx,
  name,
  href,
}: UiButtonProps): React.ReactElement {
  return (
    <ThemeProvider theme={theme}>
      <Button
        variant={variant}
        size={size}
        disabled={disabled}
        fullWidth={fullWidth}
        type={type}
        onClick={onClick}
        sx={sx}
        name={name}
        href={href}
      >
        {children}
      </Button>
    </ThemeProvider>
  );
}

export default UiButton;
