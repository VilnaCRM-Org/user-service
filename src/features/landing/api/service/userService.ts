import { gql } from '@apollo/client';

import client from '../graphql/apollo';

const CREATE_USER = gql`
  mutation CreateUser($email: String!, $initials: String!, $password: String!) {
    createUser(input: { email: $email, initials: $initials, password: $password }) {
      user {
        id
        email
        initials
      }
    }
  }
`;

export const createUser = async (email: string, initials: string, password: string) => {
  try {
    // const response = await client.mutate({
    //   mutation: CREATE_USER,
    //   variables: { email, initials, password },
    // });
    //
    // return response.data.createUser.user;

    await new Promise<void>((resolve) => {
      setTimeout(() => {
        resolve();
      }, 2000);
    });

    return { id: Math.random().toString(), initials, email };
  } catch (error) {
    throw error;
  }
};
