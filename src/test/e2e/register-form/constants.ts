import { faker } from '@faker-js/faker';

import { ExpectationEmail, ExpectationsPassword, User } from './types';

export const placeholderInitials: string = 'Mykhailo Svitskyi';
export const placeholderEmail: string = 'vilnaCRM@gmail.com';
export const placeholderPassword: string = 'Create a password';
export const signUpButton: string = 'Sign-Up';
export const authSection: string = 'auth-section';
export const policyText: string = 'I have read and accept the';

export const userData: User = {
  fullName: faker.person.fullName(),
  email: faker.internet.email(),
  password: faker.internet.password({ length: 16, prefix: 'Q9' }),
};

const textShortText: string = faker.internet.password({
  length: 7,
});

const textNoNumbers: string = faker.internet.password({
  length: 10,
  pattern: /[A-Z]/,
});
const textNoUppercaseLetter: string = faker.internet.password({
  length: 10,
  pattern: /[a-z]/,
  prefix: '1',
});

const emailWithoutDot: string = 'test@test';
const InvalidEmail: string = 'test@test.';

export const expectationsEmail: ExpectationEmail[] = [
  {
    errorText: "Must contain the characters '@' and '.'",
    email: emailWithoutDot,
  },
  { errorText: 'Invalid email address', email: InvalidEmail },
];

export const expectationsPassword: ExpectationsPassword[] = [
  { errorText: 'Requires 8 to 64 characters', password: textShortText },
  {
    errorText: 'At least one number is required',
    password: textNoNumbers,
  },
  {
    errorText: 'At least one uppercase letter',
    password: textNoUppercaseLetter,
  },
];

export const expectationsRequired: { text: string }[] = [{ text: 'This field is required' }];
