import { RegisterItem } from '../../../types/authentication/form';

export interface AuthFormProps {
  onSubmit: (data: RegisterItem) => void;
  error?: string;
}
