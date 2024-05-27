import { SxProps, Theme } from '@mui/material';

export interface UiTypographyProps {
  sx?: SxProps<Theme>;
  variant?:
    | 'h1'
    | 'h2'
    | 'h3'
    | 'h4'
    | 'h5'
    | 'h6'
    | 'medium16'
    | 'medium15'
    | 'medium14'
    | 'regular16'
    | 'bodyText18'
    | 'bodyText16'
    | 'bold22'
    | 'demi18'
    | 'button'
    | 'mobileText';
  children: React.ReactNode;
  component?: 'section' | 'p' | 'div' | 'span' | 'a' | 'h1' | 'h2' | 'h3' | 'h4' | 'h5' | 'h6';
  id?: string;
  role?: React.AriaRole;
}
