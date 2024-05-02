import { render } from '@testing-library/react';

import AuthButtons from '../../features/landing/components/Header/AuthButtons/AuthButtons';

const buttonLogInTestId: string = 'Log in';
const buttonSignUpTestId: string = 'Try it out';

it('should render two buttons with correct text and styles', () => {
  const { getByText } = render(<AuthButtons />);

  const logInButton: HTMLElement = getByText(buttonLogInTestId);
  const signUpButton: HTMLElement = getByText(buttonSignUpTestId);

  expect(logInButton).toBeInTheDocument();
  expect(signUpButton).toBeInTheDocument();
});
