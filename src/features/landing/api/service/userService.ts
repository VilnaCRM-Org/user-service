import { TypedDocumentNode, gql } from '@apollo/client';

type SignupMutationVariables = {
  FullName: string;
  Email: string;
  Password: string;
};
export const SIGNUP_MUTATION: TypedDocumentNode<SignupMutationVariables> = gql`
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
