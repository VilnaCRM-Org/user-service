import { render } from '@testing-library/react';
import React from 'react';

import AuthSection from './AuthSection';

const authSectionTestId: string = 'auth-section';
const mockAuthText: string = 'mock-sign-up-text';
const mockAuthForm: string = 'mock-auth-form';

jest.mock('@apollo/client', () => ({
  ApolloProvider: ({
    children,
  }: {
    children: React.ReactNode;
  }): JSX.Element => <div>{children}</div>,
}));

jest.mock('./AuthForm', () => ({
  AuthForm: (): React.ReactNode => (
    <div data-testid={mockAuthForm}>Mock AuthForm</div>
  ),
}));

jest.mock('./SignUpText', () => ({
  SignUpText: (): React.ReactNode => (
    <div data-testid={mockAuthText}>Mock SignUpText</div>
  ),
}));

describe('AuthSection', () => {
  test('renders without crashing', () => {
    const { getByTestId } = render(<AuthSection />);
    const authSection: HTMLElement = getByTestId(authSectionTestId);
    expect(authSection).toBeInTheDocument();
  });

  test('renders SignUpText component', () => {
    const { getByTestId } = render(<AuthSection />);
    const signUpText: HTMLElement = getByTestId(mockAuthText);
    expect(signUpText).toBeInTheDocument();
  });

  test('renders AuthForm component inside ApolloProvider', () => {
    const { getByTestId } = render(<AuthSection />);
    const authForm: HTMLElement = getByTestId(mockAuthForm);
    expect(authForm).toBeInTheDocument();
  });
});
