import { useMutation } from '@apollo/client';
import React from 'react';

import { SIGNUP_MUTATION } from '../../../api/service/userService';
import { RegisterItem } from '../../../types/authentication/form';
import { AuthForm } from '../AuthForm';

function ConnectedForm(): React.ReactElement {
  const [signupMutation] = useMutation(SIGNUP_MUTATION);

  const onSubmit: (data: RegisterItem) => Promise<void> = async (
    data: RegisterItem
  ) => {
    try {
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
    } catch (signupError) {
      throw new Error('Something went wrong. Please try again later');
    }
  };

  return <AuthForm onSubmit={onSubmit} />;
}

export default ConnectedForm;
