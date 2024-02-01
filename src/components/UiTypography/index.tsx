/* eslint-disable react/jsx-props-no-spreading */
import { ThemeProvider, Typography } from '@mui/material';
import { TypographyProps } from '@mui/material/Typography';
import React from 'react';

import theme from './Theme';

export default function UiTypography(
  props: TypographyProps
): React.ReactElement {
  const { children } = props;
  return (
    <ThemeProvider theme={theme}>
      <Typography {...props}>{children}</Typography>
    </ThemeProvider>
  );
}
