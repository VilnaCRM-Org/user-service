export interface UiButtonProps {
  variant: 'outlined' | 'contained';
  onClick?: () => void;
  sx?: Record<string, unknown>;
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
