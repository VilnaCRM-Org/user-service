import { render, fireEvent } from '@testing-library/react';
import React from 'react';

import { UiInput } from '@/components';

import { testText, testEmail, testPlaceholder } from './constants';

const testType: string = 'email';

describe('UiInput', () => {
  it('renders the input with the provided props', () => {
    const { getByPlaceholderText } = render(
      <UiInput placeholder={testPlaceholder} type={testType} value={testEmail} />
    );
    const inputElement: HTMLElement = getByPlaceholderText(testPlaceholder);
    expect(inputElement).toBeInTheDocument();
    expect(inputElement).toHaveAttribute('type', testType);
    expect(inputElement).toHaveValue(testEmail);
  });

  it('calls the onChange function when the input value changes', () => {
    const mockOnChange: () => void = jest.fn();
    const { getByRole } = render(<UiInput onChange={mockOnChange} />);
    const inputElement: HTMLElement = getByRole('textbox');
    fireEvent.change(inputElement, { target: { value: testText } });
    expect(mockOnChange).toHaveBeenCalled();
  });

  it('calls the onBlur function when the input loses focus', () => {
    const mockOnBlur: () => void = jest.fn();
    const { getByRole } = render(<UiInput onBlur={mockOnBlur} />);
    const inputElement: HTMLElement = getByRole('textbox');
    fireEvent.blur(inputElement);
    expect(mockOnBlur).toHaveBeenCalled();
  });

  it('applies the correct styles based on the error prop', () => {
    const { rerender, getByRole } = render(<UiInput error={false} />);
    let inputElement: HTMLElement = getByRole('textbox');
    expect(inputElement).toBeInTheDocument();
    expect(inputElement).toHaveAttribute('aria-invalid', 'false');

    rerender(<UiInput error />);
    inputElement = getByRole('textbox');
    expect(inputElement).toBeInTheDocument();
    expect(inputElement).toHaveAttribute('aria-invalid', 'true');
  });

  it('disables the input when the disabled prop is true', () => {
    const { getByRole } = render(<UiInput disabled />);
    const inputElement: HTMLElement = getByRole('textbox');
    expect(inputElement).toBeDisabled();
  });
});
