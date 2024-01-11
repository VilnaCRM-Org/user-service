import {
  FormControlLabel,
  Checkbox,
  ThemeProvider,
  createTheme,
} from '@mui/material';
import React from 'react';

const theme = createTheme({
  components: {
    MuiCheckbox: {
      styleOverrides: {
        root: {},
      },
    },
  },
});

function UiCheckbox({ label }: { label: string | React.ReactNode }) {
  return (
    <ThemeProvider theme={theme}>
      <FormControlLabel
        sx={{
          pt: '20px',
          pb: '26px',
          mx: '0px',
        }}
        control={
          <Checkbox
            inputProps={{
              'aria-label': 'Checkbox A',
            }}
          />
        }
        label={label}
      />
    </ThemeProvider>
  );
}

export default UiCheckbox;
