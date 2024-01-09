export interface UiButtonProps {
  variant: 'outlined' | 'contained';
  size: 'small' | 'medium' | 'large';
  disabled?: boolean;
  disableElevation?: boolean;
  disableFocusRipple?: boolean;
  disableRipple?: boolean;
  fullWidth?: boolean;
  href?: string;
  type?: 'button' | 'submit' | 'reset';
  children?: React.ReactNode | string;
}