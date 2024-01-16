/* eslint-disable react/require-default-props */
/* eslint-disable react/jsx-props-no-spreading */
import { Link, ThemeProvider } from '@mui/material';
import { LinkProps } from '@mui/material/Link';
import React from 'react';

import { theme } from './theme';

function UiLink({
  children,
  props,
}: {
  children: React.ReactNode;
  props?: LinkProps;
}) {
  return (
    <ThemeProvider theme={theme}>
      <Link {...props}>{children}</Link>
    </ThemeProvider>
  );
}

export default UiLink;
