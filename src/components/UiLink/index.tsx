/* eslint-disable react/jsx-props-no-spreading */
import { Link, ThemeProvider } from '@mui/material';
import React from 'react';

import { theme } from './theme';
import { UiLinkProps } from './types';

function UiLink({ children, props }: UiLinkProps) {
  return (
    <ThemeProvider theme={theme}>
      <Link {...props}>{children}</Link>
    </ThemeProvider>
  );
}

export default UiLink;
