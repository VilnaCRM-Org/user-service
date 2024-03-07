import { useMutation } from '@apollo/client';
import React from 'react';

import { SIGNUP_MUTATION } from '../../../api/service/userService';
import { RegisterItem } from '../../../types/authentication/form';
import { AuthForm } from '../AuthForm';

interface ErrorData {
  message: string;
}

function ConnectedForm(): React.ReactElement {
  const [signupMutation] = useMutation(SIGNUP_MUTATION);
  const [serverError, setServerError] = React.useState('');

  const onSubmit: (data: RegisterItem) => Promise<void> = async (
    data: RegisterItem
  ) => {
    try {
      setServerError('');
      await signupMutation({
        variables: {
          input: {
            email: data.Email,
            initials: data.FullName,
            clientMutationId: '132',
            password: data.Password,
          },
        },
      });
    } catch (errorData) {
      setServerError((errorData as ErrorData)?.message);
    }
  };

  return <AuthForm onSubmit={onSubmit} error={serverError} />;
}

export default ConnectedForm;
