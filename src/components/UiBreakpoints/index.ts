import { Theme, createTheme } from '@mui/material';

const breakpointsTheme: Theme = createTheme({
  breakpoints: {
    values: {
      xs: 374,
      sm: 639,
      md: 767,
      lg: 1023,
      xl: 1439,
    },
  },
});
export default breakpointsTheme;
