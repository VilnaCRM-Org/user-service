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
          svg: { display: 'none' },
          appearance: 'none',
          width: '24px',
          height: '24px',
          borderRadius: '8px',
          border: '1px solid  #D0D4D8',
          background: '#FFF',
          '&:hover': {
            border: '1px solid  #1EAEFF',
          },
        }, // need a little help
      },
    },
  },
});

function UICheckbox({ label }: { label: string | React.ReactNode }) {
  return (
    <ThemeProvider theme={theme}>
      <FormControlLabel
        sx={{ pt: '20px', pb: '32px' }}
        control={<Checkbox />}
        label={label}
      />
    </ThemeProvider>
  );
}

export default UICheckbox;
