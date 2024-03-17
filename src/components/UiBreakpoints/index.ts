import { Theme, createTheme } from '@mui/material';

const breakpointsTheme: Theme = createTheme({
  breakpoints: {
    values: {
      xs: 375,
      sm: 640,
      md: 768,
      lg: 1024,
      xl: 1440,
    },
  },
});

export default breakpointsTheme;
