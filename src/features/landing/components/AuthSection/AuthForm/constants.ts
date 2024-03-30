import { faker } from '@faker-js/faker';

import { Mock } from './types';

export const fullNamePlaceholder: RegExp = /Mykhailo Svitskyi/;
export const emailPlaceholder: string = 'vilnaCRM@gmail.com';
export const passwordPlaceholder: string = 'Create a password';
export const submitButton: string = 'Sign-Up';
export const authFormTestId: string = 'auth-form';

export const email: string = faker.internet.email();
export const password: string = faker.internet.password();
export const fullName: string = faker.internet.userName();

export const mocks: Mock[] = [
  {
    request: {
      variables: {
        input: {
          email,
          initials: fullName,
          clientMutationId: '132',
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
