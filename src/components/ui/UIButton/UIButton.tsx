import {
  Interpolation,
  Theme,
  Button,
  ThemeProvider,
  createTheme,
} from '@mui/material';
import React from 'react';

import { UIButtonProps } from './UiButtonTypes';

const defaultProps: UIButtonProps = {
  variant: 'contained',
  size: 'small',
  disabled: false,
  disableElevation: false,
  disableFocusRipple: false,
  disableRipple: false,
  fullWidth: false,
  href: '',
  children: '',
};

export const repeatStyles: Interpolation<{ theme: Theme }> = {
  textTransform: 'none',
  textDecoration: 'none',
  fontSize: '0.938rem',
  fontWeight: '500',
  padding: '16px 24px',
  lineHeight: '1.125',
};

const theme = createTheme({
  components: {
    MuiButton: {
      defaultProps,
      variants: [
        {
          props: {
            variant: 'contained',
            size: 'small',
          },
          style: {
            ...repeatStyles,
            // width: '137px',
            // height: '50px',
            backgroundColor: '#1EAEFF',
            borderRadius: '3.563rem',
            '&:hover': {
              backgroundColor: ' #00A3FF',
            },
            '&:active': {
              backgroundColor: ' #0399ED',
            },
            '&:disabled': {
              backgroundColor: '#E1E7EA',
              color: '#fff',
            },
          },
        },
        {
          props: {
            variant: 'contained',
            size: 'medium',
          },
          style: {
            textTransform: 'none',
            textDecoration: 'none',
            borderRadius: '3.563rem',
            backgroundColor: '#1EAEFF',
            padding: '1.25rem 2rem',
            alignSelf: 'center',
            fontFamily: 'Golos',
            fontWeight: '600',
            fontSize: '1.125rem',
            lineHeight: 'normal',
            '&:hover': {
              backgroundColor: ' #00A3FF',
            },
            '&:active': {
              backgroundColor: ' #0399ED',
            },
            '&:disabled': {
              backgroundColor: '#E1E7EA',
              color: '#fff',
            },
          },
        },
        {
          props: {
            variant: 'outlined',
            size: 'small',
          },
          style: {
            // width: '93px',
            // height: '50px',
            color: 'black',
            backgroundColor: 'white',
            border: '1px solid #969B9D',
            borderRadius: '3.563rem',
            ...repeatStyles,
            '&:hover': {
              backgroundColor: '#EAECEE',
              border: '1px solid rgba(0,0,0,0)',
            },
            '&:active': {
              border: '1px solid #EAECEE',
            },
            '&:disabled': {
              backgroundColor: '#E1E7EA',
              color: '#fff',
              border: 'none',
            },
          },
        },
      ],
    },
  },
});

function UIButton({
  children,
  variant,
  size,
  disabled,
  disableFocusRipple,
  disableRipple,
  disableElevation,
  href,
  fullWidth,
  type,
}: UIButtonProps) {
  return (
    <ThemeProvider theme={theme}>
      <Button
        variant={variant}
        size={size}
        disabled={disabled}
        disableElevation={disableElevation}
        disableFocusRipple={disableFocusRipple}
        disableRipple={disableRipple}
        fullWidth={fullWidth}
        type={type}
        href={href}
      >
        {children}
      </Button>
    </ThemeProvider>
  );
}

export default UIButton;
