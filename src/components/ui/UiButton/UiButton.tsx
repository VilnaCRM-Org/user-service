import {
  Interpolation,
  Theme,
  Button,
  ThemeProvider,
  createTheme,
} from '@mui/material';
import React from 'react';

import { UiButtonProps } from './UiButtonTypes';

const defaultProps: UiButtonProps = {
  variant: 'contained',
  size: 'small',
  disabled: false,
  disableElevation: false,
  disableFocusRipple: false,
  disableRipple: false,
  fullWidth: false,
  href: '',
  sx: {},
  onClick: () => {},
  children: '',
};

export const repeatStyles: Interpolation<{ theme: Theme }> = {
  textTransform: 'none',
  textDecoration: 'none',
  fontSize: '0.938rem',
  fontWeight: '500',
  fontFamily: 'Golos',
  lineHeight: '1.125',
  letterSpacing: '0',
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
            backgroundColor: '#1EAEFF',
            borderRadius: '3.563rem',
            padding: '16px 24px',
            '&:hover': {
              backgroundColor: '#00A3FF',
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
            fontSize: '18px',
            letterSpacing: '0',
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
            '@media (max-width: 639.98px)': {
              fontSize: '15px',
              fontWeight: '400',
              lineHeight: '18px',
              padding: '16px 23px',
            },
          },
        },
        {
          props: {
            variant: 'outlined',
            size: 'small',
          },
          style: {
            ...repeatStyles,
            color: '#1B2327',
            padding: '16px 23px',
            backgroundColor: 'white',
            border: '1px solid #969B9D',
            borderRadius: '3.563rem',
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

function UiButton({
  onClick,
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
  sx,
}: UiButtonProps) {
  return (
    <ThemeProvider theme={theme}>
      <Button
        sx={sx}
        onClick={onClick}
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

export default UiButton;
