import {
  createTheme,
  InputLabel,
  ThemeProvider,
  Typography,
} from '@mui/material';

import { labelProps } from './UILabelType';

const theme = createTheme({});

function UILabel({ children, errorText, hasError, title }: labelProps) {
  return (
    <ThemeProvider theme={theme}>
      <Typography sx={{ paddingBottom: '9px' }}>{title}</Typography>
      <InputLabel>{children}</InputLabel>
      {hasError ? (
        <Typography sx={{ color: 'red', pt: '4px' }} variant="body2">
          {errorText}
        </Typography>
      ) : null}
    </ThemeProvider>
  );
}
export default UILabel;
