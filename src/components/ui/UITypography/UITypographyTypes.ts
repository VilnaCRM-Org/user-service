import React from 'react';

export interface UITypographyProps {
  variant?:
    | 'h1'
    | 'h2'
    | 'h3'
    | 'h4'
    | 'h5'
    | 'h6'
    | 'subtitle1'
    | 'subtitle2'
    | 'body1'
    | 'body2'
    | 'button'
    | 'caption'
    | 'overline'
    | 'inherit'
    | 'bodyText18'
    | 'bodyText16'
    | 'bold22'
    | 'demi18'
    | 'regular16'
    | 'medium16'
    | 'medium14'
    | 'medium15'
    | 'button';
  children?: React.ReactNode | string;
  sx?: object;
  component?: React.ElementType;
}
