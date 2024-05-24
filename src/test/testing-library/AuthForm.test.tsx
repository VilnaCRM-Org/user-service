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
  submitButtonText,
  mocks,
  authFormSelector,
  mockErrors,
  formTitleText,
  nameInputText,
  emailInputText,
  passwordInputText,
  requiredText,
  statusRole,
  checkboxRole,
  alertRole,
  emptyValue,
  passwordTipAltText,
  buttonRole,
  borderStyle,
} from '../../features/landing/components/AuthSection/AuthForm/constants';

import { testInitials, testEmail, testPassword } from './constants';

describe('AuthForm', () => {
  it('renders AuthForm component', () => {
    const {
      container,
      queryByRole,
      getByPlaceholderText,
      getByAltText,
      getByText,
      getByRole,
    } = render(
      <MockedProvider>
        <AuthForm />
      </MockedProvider>
    );

    const authForm: HTMLElement | null =
      container.querySelector(authFormSelector);
    const formTitle: HTMLElement = getByText(formTitleText);
    const nameInputLabel: HTMLElement = getByText(nameInputText);
    const emailInputLabel: HTMLElement = getByText(emailInputText);
    const passwordInputLabel: HTMLElement = getByText(passwordInputText);
    const passwordTipImage: HTMLElement = getByAltText(passwordTipAltText);

    const fullNameInput: HTMLInputElement = getByPlaceholderText(
      fullNamePlaceholder
    ) as HTMLInputElement;
    const emailInput: HTMLInputElement = getByPlaceholderText(
      emailPlaceholder
    ) as HTMLInputElement;
    const passwordInput: HTMLInputElement = getByPlaceholderText(
      passwordPlaceholder
    ) as HTMLInputElement;
    const privacyCheckbox: HTMLInputElement = getByRole(
      checkboxRole
    ) as HTMLInputElement;

    const serverErrorMessage: HTMLElement | null = queryByRole(alertRole);
    const loader: HTMLElement | null = queryByRole(statusRole);

    expect(fullNameInput.value).toBe(emptyValue);
    expect(emailInput.value).toBe(emptyValue);
    expect(passwordInput.value).toBe(emptyValue);
    expect(privacyCheckbox).not.toBeChecked();

    expect(authForm).toBeInTheDocument();
    expect(formTitle).toBeInTheDocument();
    expect(nameInputLabel).toBeInTheDocument();
    expect(emailInputLabel).toBeInTheDocument();
    expect(passwordInputLabel).toBeInTheDocument();
    expect(passwordTipImage).toBeInTheDocument();
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
    const { getByRole, getByPlaceholderText } = render(
      <MockedProvider addTypename={false}>
        <AuthForm />
      </MockedProvider>
    );

    const fullNameInput: HTMLElement =
      getByPlaceholderText(fullNamePlaceholder);
    const emailInput: HTMLElement = getByPlaceholderText(emailPlaceholder);
    const passwordInput: HTMLElement =
      getByPlaceholderText(passwordPlaceholder);
    const privacyCheckbox: HTMLElement = getByRole(checkboxRole);
    const signUpButton: HTMLElement = getByRole(buttonRole, {
      name: submitButtonText,
    });

    fireEvent.change(fullNameInput, { target: { value: fullName } });
    fireEvent.change(emailInput, { target: { value: email } });
    fireEvent.change(passwordInput, { target: { value: password } });
    fireEvent.click(privacyCheckbox);
    fireEvent.click(signUpButton);

    await waitFor(() => {
      const loader: HTMLElement = getByRole(statusRole);

      expect(loader).toBeInTheDocument();
      expect(mocks[0].result).resolves.toHaveProperty('status', 200);
    });
  });

  it('registration with server error', async () => {
    const { getByRole, getByPlaceholderText } = render(
      <MockedProvider addTypename={false}>
        <AuthForm />
      </MockedProvider>
    );

    const fullNameInput: HTMLElement =
      getByPlaceholderText(fullNamePlaceholder);
    const emailInput: HTMLElement = getByPlaceholderText(emailPlaceholder);
    const passwordInput: HTMLElement =
      getByPlaceholderText(passwordPlaceholder);
    const privacyCheckbox: HTMLElement = getByRole(checkboxRole);
    const signUpButton: HTMLElement = getByRole(buttonRole, {
      name: submitButtonText,
    });

    fireEvent.change(emailInput, { target: { value: testEmail } });
    fireEvent.change(passwordInput, { target: { value: testPassword } });
    fireEvent.change(fullNameInput, { target: { value: testInitials } });
    fireEvent.click(privacyCheckbox);
    fireEvent.click(signUpButton);

    await waitFor(() => {
      const serverErrorMessage: HTMLElement | null = getByRole(alertRole);

      expect(serverErrorMessage).toBeInTheDocument();
      expect(mockErrors[0].error).toBeDefined();
    });
  });

  it('correct linkage between inputs and values', async () => {
    const { getByPlaceholderText, getByRole } = render(
      <MockedProvider addTypename={false}>
        <AuthForm />
      </MockedProvider>
    );

    const fullNameInput: HTMLInputElement = getByPlaceholderText(
      fullNamePlaceholder
    ) as HTMLInputElement;
    const emailInput: HTMLInputElement = getByPlaceholderText(
      emailPlaceholder
    ) as HTMLInputElement;
    const passwordInput: HTMLInputElement = getByPlaceholderText(
      passwordPlaceholder
    ) as HTMLInputElement;
    const privacyCheckbox: HTMLInputElement = getByRole(
      checkboxRole
    ) as HTMLInputElement;

    fireEvent.change(emailInput, { target: { value: email } });
    fireEvent.change(passwordInput, { target: { value: password } });
    fireEvent.change(fullNameInput, { target: { value: fullName } });
    fireEvent.click(privacyCheckbox);

    await waitFor(() => {
      expect(emailInput.value).toBe(email);
      expect(passwordInput.value).toBe(password);
      expect(fullNameInput.value).toBe(fullName);
      expect(privacyCheckbox).toBeChecked();
    });
  });

  it('correct linkage between inputs and values with no data', async () => {
    const { getByPlaceholderText, getByRole, getAllByText, queryByRole } =
      render(
        <MockedProvider addTypename={false}>
          <AuthForm />
        </MockedProvider>
      );

    const fullNameInput: HTMLInputElement = getByPlaceholderText(
      fullNamePlaceholder
    ) as HTMLInputElement;
    const emailInput: HTMLInputElement = getByPlaceholderText(
      emailPlaceholder
    ) as HTMLInputElement;
    const passwordInput: HTMLInputElement = getByPlaceholderText(
      passwordPlaceholder
    ) as HTMLInputElement;
    let privacyCheckbox: HTMLInputElement = getByRole(
      checkboxRole
    ) as HTMLInputElement;
    const signUpButton: HTMLElement = getByRole(buttonRole, {
      name: submitButtonText,
    });

    fireEvent.change(emailInput, { target: { value: emptyValue } });
    fireEvent.change(passwordInput, { target: { value: emptyValue } });
    fireEvent.change(fullNameInput, { target: { value: emptyValue } });
    fireEvent.click(signUpButton);

    await waitFor(() => {
      const requiredError: HTMLElement[] = getAllByText(requiredText);
      const serverErrorMessage: HTMLElement | null = queryByRole(alertRole);
      privacyCheckbox = getByRole(checkboxRole) as HTMLInputElement;

      expect(emailInput.value).toBe(emptyValue);
      expect(passwordInput.value).toBe(emptyValue);
      expect(fullNameInput.value).toBe(emptyValue);
      expect(privacyCheckbox).not.toBeChecked();
      expect(privacyCheckbox).toHaveStyle(borderStyle);

      expect(requiredError.length).toBe(3);
      expect(serverErrorMessage).not.toBeInTheDocument();

      expect(mockErrors[0].error).toBeDefined();
    });
  });
});
