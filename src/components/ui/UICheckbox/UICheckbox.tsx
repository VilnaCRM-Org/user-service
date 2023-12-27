import {
  FormControlLabel,
  Checkbox,
  ThemeProvider,
  createTheme,
  Stack,
} from '@mui/material';
import React from 'react';

import UITypography from '../UITypography/UITypography';

const theme = createTheme({
  components: {
    MuiCheckbox: {
      styleOverrides: {
        root: {
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

function UICheckbox() {
  return (
    <ThemeProvider theme={theme}>
      <FormControlLabel
        sx={{ pt: '20px', pb: '32px' }}
        control={<Checkbox />}
        label={
          <UITypography variant="medium14">
            Я прочитав та приймаю Політику Конфіденційності та Політику
            Використання сервісу VilnaCRM
          </UITypography>
        }
      />
    </ThemeProvider>
  );
}

export default UICheckbox;
