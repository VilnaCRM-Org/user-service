import { render, fireEvent } from '@testing-library/react';
import React from 'react';

import { UiCheckbox } from '@/components';

import { testText } from './constants';

const mockOnChange: () => void = jest.fn();

const borderStyle: string = 'border: 1px solid #DC3939';

describe('UiCheckbox', () => {
  it('renders the checkbox with the provided label', () => {
    const { getByLabelText } = render(<UiCheckbox label={testText} onChange={mockOnChange} />);
    const checkboxLabel: HTMLElement = getByLabelText(testText);
    expect(checkboxLabel).toBeInTheDocument();
  });

  it('calls the onChange function when the checkbox is clicked', () => {
    const { getByRole } = render(<UiCheckbox onChange={mockOnChange} label={testText} />);
    const checkboxInput: HTMLElement = getByRole('checkbox');
    fireEvent.click(checkboxInput);
    expect(mockOnChange).toHaveBeenCalled();
  });

  it('disables the checkbox when the disabled prop is true', () => {
    const { getByRole } = render(<UiCheckbox disabled onChange={mockOnChange} label={testText} />);
    const checkboxInput: HTMLElement = getByRole('checkbox');
    expect(checkboxInput).toBeDisabled();
  });

  it('renders the checkbox with the provided error', () => {
    const { getByLabelText, getByRole } = render(
      <UiCheckbox error onChange={mockOnChange} label={testText} />
    );
    const checkboxLabel: HTMLElement = getByLabelText(testText);
    const checkboxInput: HTMLElement = getByRole('checkbox');
    expect(checkboxLabel).toBeInTheDocument();
    expect(checkboxInput).toHaveStyle(borderStyle);
  });
});
