import { render } from '@testing-library/react';

import '@testing-library/jest-dom';
import AuthButtons from './AuthButtons';

const buttonLogInTestId: string = 'header-log-in';
const buttonSignUpTestId: string = 'header-sign-up';
const buttonLogInText: string = 'header.actions.log_in';
const buttonSignUpText: string = 'header.actions.try_it_out';

it('should render two buttons with correct text and styles', () => {
  const { getByTestId } = render(<AuthButtons />);

  const logInButton: HTMLElement = getByTestId(buttonLogInTestId);
  const signUpButton: HTMLElement = getByTestId(buttonSignUpTestId);

  expect(logInButton).toBeInTheDocument();
  expect(logInButton).toHaveTextContent(buttonLogInText);

  expect(signUpButton).toBeInTheDocument();
  expect(signUpButton).toHaveTextContent(buttonSignUpText);
});
