import { TypedDocumentNode, gql, useMutation } from '@apollo/client';
import React from 'react';

import { RegisterItem } from '../../../types/authentication/form';
import { AuthForm } from '../AuthForm';

type SignupMutationVariables = {
  FullName: string;
  Email: string;
  Password: string;
};

function ConnectedLoginForm(): React.ReactElement {
  const SIGNUP_MUTATION: TypedDocumentNode<SignupMutationVariables> = gql`
    mutation signup(
      $FullName: String!
      $Email: String!
      $Password: String!
      $id: String!
    ) {
      data(FullName: $FullName, Email: $Email, Password: $Password, id: $id) {
        FullName
        Email
        Password
        id
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
          FullName: data.FullName,
          Email: data.Email,
          Password: data.Password,
          id: '1',
        },
      });
      console.log('Signup successful:', Response);
    } catch (signupError) {
      console.error('Signup error:', signupError);
    }
  };

  return <AuthForm onSubmit={onSubmit} />;
}

export default ConnectedLoginForm;
