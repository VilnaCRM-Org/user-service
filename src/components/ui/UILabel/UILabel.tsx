import { createTheme, InputLabel, ThemeProvider } from '@mui/material';

import UITypography from '../UITypography/UITypography';

import { labelProps } from './UILabelType';

const theme = createTheme({});

function UILabel({ children, sx, errorText, hasError, title }: labelProps) {
  return (
    <ThemeProvider theme={theme}>
      <UITypography variant="medium14" sx={sx}>
        {title}
      </UITypography>
      <InputLabel>{children}</InputLabel>
      {hasError ? (
        <UITypography sx={{ color: 'red', pt: '4px' }} variant="medium14">
          {errorText}
        </UITypography>
      ) : null}
    </ThemeProvider>
  );
}
export default UILabel;
