import { faker } from '@faker-js/faker';

import { Mock } from './types';

export const fullNamePlaceholder: RegExp = /Mykhailo Svitskyi/;
export const emailPlaceholder: string = 'vilnaCRM@gmail.com';
export const passwordPlaceholder: string = 'Create a password';

export const submitButtonText: string = 'Sign-Up';
export const formTitleText: string = 'Or register on the website:';
export const nameInputText: string = 'Your name and surname';
export const emailInputText: string = 'Email';
export const passwordInputText: string = 'Password';
export const requiredText: string = 'This field is required';
export const passwordTipAltText: string = 'Password tip mark';

export const authFormSelector: string = '.MuiBox-root';

export const statusRole: string = 'status';
export const checkboxRole: string = 'checkbox';
export const alertRole: string = 'alert';
export const buttonRole: string = 'button';

export const emptyValue: string = '';

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
