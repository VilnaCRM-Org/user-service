/* eslint-disable react/jsx-props-no-spreading */
import { Link, ThemeProvider, createTheme } from '@mui/material';
import React from 'react';

const theme = createTheme({
  components: {
    MuiLink: {
      styleOverrides: {
        root: {
          color: '#1EAEFF',
          fontFamily: 'Inter',
          fontSize: '14px',
          fontStyle: 'normal',
          fontWeight: '700',
          lineHeight: '18px',
          textDecoration: 'underline',
        },
      },
    },
  },
});

function UiLink({
  children,
  props,
}: {
  children: React.ReactNode;
  props: any;
}) {
  return (
    <ThemeProvider theme={theme}>
      <Link href="#" {...props}>
        {children}
      </Link>
    </ThemeProvider>
  );
}

export default UiLink;
