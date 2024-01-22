import { CheckboxProps } from '@mui/material';

export interface UiCheckboxProps {
  onChange: (event: React.ChangeEvent<HTMLInputElement>) => void;
  label: string | React.ReactNode;
  sx?: Record<string, unknown>;
  props?: CheckboxProps;
}
