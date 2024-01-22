import { createTheme } from '@mui/material';

export const theme = createTheme({
  components: {
    MuiCheckbox: {
      styleOverrides: {
        root: {
          '&.Mui-disabled svg': {
            fill: 'red',
          },
        },
      },
    },
  },
});
