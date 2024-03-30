import { render } from '@testing-library/react';

import AuthButtons from './AuthButtons';

const buttonLogInTestId: string = 'header-log-in';
const buttonSignUpTestId: string = 'header-sign-up';

it('should render two buttons with correct text and styles', () => {
  const { getByTestId } = render(<AuthButtons />);

  const logInButton: HTMLElement = getByTestId(buttonLogInTestId);
  const signUpButton: HTMLElement = getByTestId(buttonSignUpTestId);

  expect(logInButton).toBeInTheDocument();
  expect(signUpButton).toBeInTheDocument();
});
