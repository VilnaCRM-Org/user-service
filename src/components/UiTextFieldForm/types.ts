import { TextFieldProps } from '@mui/material';
import { Control, FieldValues, Path } from 'react-hook-form';

export interface CustomTextField<T extends FieldValues> extends TextFieldProps<'standard'> {
  control: Control<T>;
  rules: FieldValues;
  name: Path<T>;
  placeholder: string;
  type?: string;
  fullWidth?: boolean;
}
