import { render } from '@testing-library/react';
import React from 'react';

import AuthSection from './AuthSection';

jest.mock('@apollo/client', () => ({
  ApolloProvider: ({
    children,
  }: {
    children: React.ReactNode;
  }): JSX.Element => <div>{children}</div>,
}));

jest.mock('./AuthForm', () => ({
  AuthForm: (): React.ReactNode => (
    <div data-testid="mock-auth-form">Mock AuthForm</div>
  ),
}));

jest.mock('./SignUpText', () => ({
  SignUpText: (): React.ReactNode => (
    <div data-testid="mock-sign-up-text">Mock SignUpText</div>
  ),
}));

describe('AuthSection', () => {
  test('renders without crashing', () => {
    const { getByTestId } = render(<AuthSection />);
    const authSection: HTMLElement = getByTestId('auth-section');
    expect(authSection).toBeInTheDocument();
  });

  test('renders SignUpText component', () => {
    const { getByTestId } = render(<AuthSection />);
    const signUpText: HTMLElement = getByTestId('mock-sign-up-text');
    expect(signUpText).toBeInTheDocument();
  });

  test('renders AuthForm component inside ApolloProvider', () => {
    const { getByTestId } = render(<AuthSection />);
    const authForm: HTMLElement = getByTestId('mock-auth-form');
    expect(authForm).toBeInTheDocument();
  });
});
