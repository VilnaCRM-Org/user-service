/* eslint-disable react/jsx-props-no-spreading */
import { Typography } from '@mui/material';
import { TypographyProps } from '@mui/material/Typography';
import React from 'react';

import defaultTypography from './DefaultTypography';

function UiTypography(props: TypographyProps): React.ReactElement {
  const { children } = props;
  return <Typography {...props}>{children}</Typography>;
}
export const DefaultTypography: React.FC<TypographyProps> =
  defaultTypography(UiTypography);

export default UiTypography;
