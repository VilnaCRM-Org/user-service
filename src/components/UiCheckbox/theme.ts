import { createTheme } from '@mui/material';

export const theme = createTheme({
  components: {
    MuiCheckbox: {
      styleOverrides: {
        root: {
          // '&:hover': {
          //   '&.MuiSvgIcon-root': {
          //     color: 'red',
          //     borderRadius: '8px',
          //     border: '1px solid red',
          //     backgroundColor: 'red',
          //     width: ' 28px',
          //     height: '28px',
          //     fontSize: '28px',
          //   },
          //   '.MuiCheckbox-root': {
          //     color: 'red',
          //     borderRadius: '8px',
          //     border: '1px solid red',
          //     backgroundColor: 'red',
          //     width: ' 24px',
          //     height: '24px',
          //   },
          // },
          // '&.MuiCheckbox-root .Mui-disabled': {
          //   borderRadius: '8px',
          //   backgroundColor: 'red',
          //   width: ' 24px',
          //   height: '24px',
          // },
          '&.Mui-disabled svg': {
            fill: 'red',
          },
        },
      },
    },
  },
});
