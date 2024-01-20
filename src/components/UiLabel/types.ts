import { ReactNode } from 'react';

export interface labelProps {
  sx?: object;
  children: ReactNode;
  title?: string;
  errorText?: string;
  hasError?: boolean | undefined;
}
