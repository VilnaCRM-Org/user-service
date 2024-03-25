export interface UiCheckboxProps {
  onChange: (event: React.ChangeEvent<HTMLInputElement>) => void;
  label: string | React.ReactNode;
  disabled?: boolean;
  sx?: React.CSSProperties;
  error?: boolean;
}
