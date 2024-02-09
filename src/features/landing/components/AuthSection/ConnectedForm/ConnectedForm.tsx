import { TypedDocumentNode, gql, useMutation } from '@apollo/client';
import React from 'react';

import { RegisterItem } from '../../../types/authentication/form';
import { AuthForm } from '../AuthForm';

type SignupMutationVariables = {
  FullName: string;
  Email: string;
  Password: string;
};

function ConnectedForm(): React.ReactElement {
  const SIGNUP_MUTATION: TypedDocumentNode<SignupMutationVariables> = gql`
    mutation AddUser($input: createUserInput!) {
      createUser(input: $input) {
        user {
          email
          initials
          id
          confirmed
        }
        clientMutationId
      }
    }
  `;
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
      alert(signupError);
    }
  };

  return <AuthForm onSubmit={onSubmit} />;
}

export default ConnectedForm;
