import { MockedProvider } from '@apollo/client/testing';
import { render, fireEvent, waitFor } from '@testing-library/react';
import React from 'react';

import AuthForm from '../../features/landing/components/AuthSection/AuthForm/AuthForm';
import {
  fullName,
  email,
  password,
  fullNamePlaceholder,
  emailPlaceholder,
  passwordPlaceholder,
  submitButton,
  mocks,
  authFormSelector,
  mockErrors,
} from '../../features/landing/components/AuthSection/AuthForm/constants';

import { testInitials, testEmail, testPassword } from './constants';

describe('AuthForm', () => {
  it('renders AuthForm component', () => {
    const { container, queryByRole, getByPlaceholderText, getByLabelText } =
      render(
        <MockedProvider>
          <AuthForm />
        </MockedProvider>
      );

    const authForm: HTMLElement | null =
      container.querySelector(authFormSelector);
    const serverErrorMessage: HTMLElement | null = queryByRole('alert');
    const loader: HTMLElement | null = queryByRole('status');
    const fullNameInput: HTMLInputElement = getByPlaceholderText(
      fullNamePlaceholder
    ) as HTMLInputElement;
    const emailInput: HTMLInputElement = getByPlaceholderText(
      emailPlaceholder
    ) as HTMLInputElement;
    const passwordInput: HTMLInputElement = getByPlaceholderText(
      passwordPlaceholder
    ) as HTMLInputElement;

    const privacyCheckbox: HTMLInputElement = getByLabelText(
      /Privacy Policy/
    ) as HTMLInputElement;

    expect(fullNameInput.value).toBe('');
    expect(emailInput.value).toBe('');
    expect(passwordInput.value).toBe('');
    expect(privacyCheckbox).not.toBeChecked();

    expect(authForm).toBeInTheDocument();
    expect(loader).not.toBeInTheDocument();
    expect(serverErrorMessage).not.toBeInTheDocument();

    fireEvent.click(privacyCheckbox);

    expect(privacyCheckbox).toBeChecked();
  });

  it('renders input fields', () => {
    const { getByPlaceholderText } = render(
      <MockedProvider>
        <AuthForm />
      </MockedProvider>
    );
    const fullNameInput: HTMLElement =
      getByPlaceholderText(fullNamePlaceholder);
    const emailInput: HTMLElement = getByPlaceholderText(emailPlaceholder);
    const passwordInput: HTMLElement =
      getByPlaceholderText(passwordPlaceholder);

    expect(fullNameInput).toBeInTheDocument();
    expect(emailInput).toBeInTheDocument();
    expect(passwordInput).toBeInTheDocument();
  });

  it('successful registration', async () => {
    const { getByLabelText, getByRole, getByPlaceholderText } = render(
      <MockedProvider addTypename={false}>
        <AuthForm />
      </MockedProvider>
    );
    const fullNameInput: HTMLElement =
      getByPlaceholderText(fullNamePlaceholder);
    const emailInput: HTMLElement = getByPlaceholderText(emailPlaceholder);
    const passwordInput: HTMLElement =
      getByPlaceholderText(passwordPlaceholder);

    const privacyCheckbox: HTMLElement = getByLabelText(/Privacy Policy/);
    const signUpButton: HTMLElement = getByRole('button', {
      name: submitButton,
    });

    fireEvent.change(emailInput, { target: { value: email } });
    fireEvent.change(passwordInput, { target: { value: password } });
    fireEvent.change(fullNameInput, { target: { value: fullName } });
    fireEvent.click(privacyCheckbox);
    fireEvent.click(signUpButton);

    await waitFor(() =>
      expect(mocks[0].result).resolves.toHaveProperty('status', 200)
    );
  });

  it('registration with server error', async () => {
    const { getByLabelText, getByRole, getByPlaceholderText } = render(
      <MockedProvider addTypename={false}>
        <AuthForm />
      </MockedProvider>
    );

    const fullNameInput: HTMLElement =
      getByPlaceholderText(fullNamePlaceholder);
    const emailInput: HTMLElement = getByPlaceholderText(emailPlaceholder);
    const passwordInput: HTMLElement =
      getByPlaceholderText(passwordPlaceholder);
    const privacyCheckbox: HTMLElement = getByLabelText(/Privacy Policy/);
    const signUpButton: HTMLElement = getByRole('button', {
      name: submitButton,
    });

    fireEvent.change(emailInput, { target: { value: testEmail } });
    fireEvent.change(passwordInput, { target: { value: testPassword } });
    fireEvent.change(fullNameInput, { target: { value: testInitials } });
    fireEvent.click(privacyCheckbox);
    fireEvent.click(signUpButton);

    await waitFor(() => expect(mockErrors[0].erorr).toBeDefined());
  });
});
