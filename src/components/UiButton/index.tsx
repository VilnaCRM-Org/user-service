import { Button, ThemeProvider } from '@mui/material';
import React from 'react';

import { theme } from './theme';
import { UiButtonProps } from './types';

function UiButton({
  onClick,
  children,
  variant,
  size,
  disabled,
  disableFocusRipple,
  disableRipple,
  disableElevation,
  href,
  fullWidth,
  type,
  sx,
}: UiButtonProps) {
  return (
    <ThemeProvider theme={theme}>
      <Button
        sx={sx}
        onClick={onClick}
        variant={variant}
        size={size}
        disabled={disabled}
        disableElevation={disableElevation}
        disableFocusRipple={disableFocusRipple}
        disableRipple={disableRipple}
        fullWidth={fullWidth}
        type={type}
        href={href}
      >
        {children}
      </Button>
    </ThemeProvider>
  );
}

export default UiButton;
