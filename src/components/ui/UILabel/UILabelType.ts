import { ReactNode } from 'react';
import { FieldError } from 'react-hook-form';

export interface labelProps {
  children: ReactNode;
  title?: string;
  errorText?: string;
  hasError?: FieldError | undefined;
}
