/* eslint-disable react/jsx-props-no-spreading */
import { Link, LinkProps, ThemeProvider } from '@mui/material';
import React from 'react';

import { theme } from './theme';

function UiLink(props: LinkProps) {
  const { children } = props;
  return (
    <ThemeProvider theme={theme}>
      <Link {...props}>{children}</Link>
    </ThemeProvider>
  );
}

export default UiLink;
