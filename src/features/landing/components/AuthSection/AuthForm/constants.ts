import { faker } from '@faker-js/faker';

import { Mock } from './types';

export const fullNamePlaceholder: RegExp = /Mykhailo Svitskyi/;
export const emailPlaceholder: string = 'vilnaCRM@gmail.com';
export const passwordPlaceholder: string = 'Create a password';
export const submitButton: string = 'Sign-Up';
export const authFormSelector: string = '.MuiBox-root';

export const fullName: string = faker.person.fullName();
export const email: string = faker.internet.email();
export const password: string = faker.internet.password({
  length: 16,
  prefix: 'Q9',
});
export const randomClientId: string = faker.string.uuid();

export const mocks: Mock[] = [
  {
    request: {
      variables: {
        input: {
          email,
          initials: fullName,
          clientMutationId: randomClientId,
          password,
        },
      },
    },
    result: Promise.resolve({
      data: {
        signUp: {
          success: true,
        },
      },
      status: 200,
    }),
  },
];

export const mockErrors: Mock[] = [
  {
    request: {
      variables: {
        input: {
          email,
          initials: fullName,
          clientMutationId: randomClientId,
          password,
        },
      },
    },
    error: new Error('Server error occurred'),
  },
];
