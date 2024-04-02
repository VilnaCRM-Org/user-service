import { RegisterItem } from '../../../types/authentication/form';

export interface AuthFormProps {
  onSubmit: (data: RegisterItem) => void;
  error?: string;
}
interface SignUpVariables {
  input: {
    email: string;
    initials: string;
    clientMutationId: string;
    password: string;
  };
}

interface SignUpResult {
  data: {
    signUp: {
      success: boolean;
    };
  };
  status: number;
}

export interface Mock {
  request: {
    variables: SignUpVariables;
  };
  result?: Promise<SignUpResult>;
  erorr?: Error;
}
