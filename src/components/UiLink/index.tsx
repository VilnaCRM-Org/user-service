/* eslint-disable react/jsx-props-no-spreading */
import { Link, ThemeProvider } from '@mui/material';
import React from 'react';

import { theme } from './theme';
import { UiLinkProps } from './types';

function UiLink({ children, href, props }: UiLinkProps) {
  return (
    <ThemeProvider theme={theme}>
      <Link {...props} href={href}>
        {children}
      </Link>
    </ThemeProvider>
  );
}

export default UiLink;
