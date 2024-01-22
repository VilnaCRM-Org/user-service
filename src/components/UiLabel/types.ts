import { ReactNode } from 'react';

export interface labelProps {
  sx?: Record<string, unknown>;
  children: ReactNode;
  title?: string;
  errorText?: string;
  hasError?: boolean | undefined;
}
