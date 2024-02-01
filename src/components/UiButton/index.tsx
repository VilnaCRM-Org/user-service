/* eslint-disable react/jsx-props-no-spreading */
import { Button, ThemeProvider } from '@mui/material';
import { ButtonProps } from '@mui/material/Button';
import React from 'react';

import { theme } from './theme';

function UiButton(props: ButtonProps): React.ReactElement {
  const { children } = props;
  return (
    <ThemeProvider theme={theme}>
      <Button {...props}>{children}</Button>
    </ThemeProvider>
  );
}

export default UiButton;
