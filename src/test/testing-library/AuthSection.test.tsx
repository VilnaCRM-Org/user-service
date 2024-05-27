import { render } from '@testing-library/react';
import React from 'react';

import AuthSection from '../../features/landing/components/AuthSection/AuthSection';

const authSectionSelector: string = 'section';
const mockAuthText: string = 'mock-sign-up-text';
const mockAuthForm: string = 'mock-auth-form';

jest.mock('@apollo/client', () => ({
  ApolloProvider: ({ children }: { children: React.ReactNode }): JSX.Element => (
    <div>{children}</div>
  ),
}));

jest.mock('../../features/landing/components/AuthSection/AuthForm', () => ({
  AuthForm: (): React.ReactNode => <div data-testid={mockAuthForm}>Mock AuthForm</div>,
}));

jest.mock('../../features/landing/components/AuthSection/SignUpText', () => ({
  SignUpText: (): React.ReactNode => <div data-testid={mockAuthText}>Mock SignUpText</div>,
}));

describe('AuthSection', () => {
  test('renders without crashing', () => {
    const { container } = render(<AuthSection />);
    const authSection: HTMLElement | null = container.querySelector(authSectionSelector);
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
