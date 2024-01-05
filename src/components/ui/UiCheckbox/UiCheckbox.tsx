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
        root: {
          span: {},
          svg: {
            appearance: 'none',
          },
          input: {},
        }, // need a little help
      },
    },
  },
});

function UiCheckbox({ label }: { label: string | React.ReactNode }) {
  return (
    <ThemeProvider theme={theme}>
      <FormControlLabel
        sx={{ pt: '20px', pb: '26px' }}
        control={<Checkbox />}
        label={label}
      />
    </ThemeProvider>
  );
}

export default UiCheckbox;
