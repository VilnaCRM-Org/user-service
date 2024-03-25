export interface UiInputProps {
  sx?: React.CSSProperties;
  placeholder?: string;
  value?: string;
  onChange?: (event: React.ChangeEvent<HTMLInputElement>) => void;
  ref?: React.ForwardedRef<HTMLInputElement>;
  error?: boolean;
  onBlur?: (event: React.FocusEvent<HTMLInputElement>) => void;
  type?: string;
  fullWidth?: boolean;
  disabled?: boolean;
  onInput?: (event: React.ChangeEvent<HTMLInputElement>) => void;
  id?: string;
}
