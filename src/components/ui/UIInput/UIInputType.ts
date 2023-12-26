import { FieldError } from 'react-hook-form';

export interface UIInputProps {
  placeholder: string;
  hasError?: FieldError | undefined;
  type?: string
}
