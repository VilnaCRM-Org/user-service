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
  password: faker.internet.password(),
};

export const expectationsEmail: ExpectationEmail[] = [
  { errorText: "Email must contain '@' and '.' symbols", email: 'hello@sdf' },
  { errorText: 'Invalid email address', email: 'hello@sdf.' },
];

export const expectationsPassword: ExpectationsPassword[] = [
  { errorText: 'Password must be between 8', password: 'tirion' },
  {
    errorText: 'Password must contain at least one number',
    password: 'lanister',
  },
  {
    errorText: 'Password must contain at least one uppercase letter',
    password: 'lanister1',
  },
];

export const expectationsRequired: { text: string }[] = [
  { text: 'Your first and last name are' },
  { text: 'Email address is a required' },
  { text: 'Password is a required field' },
];
