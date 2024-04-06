import { faker } from '@faker-js/faker';
import { render, fireEvent } from '@testing-library/react';
import React from 'react';

import { UiCheckbox } from '@/components';

const randomText: string = faker.lorem.word(8);
describe('UiCheckbox', () => {
  it('renders the checkbox with the provided label', () => {
    const mockOnChange: () => void = jest.fn();
    const { getByLabelText } = render(
      <UiCheckbox label={randomText} onChange={mockOnChange} />
    );
    const checkboxLabel: HTMLElement = getByLabelText(randomText);
    expect(checkboxLabel).toBeInTheDocument();
  });

  it('calls the onChange function when the checkbox is clicked', () => {
    const mockOnChange: () => void = jest.fn();
    const { getByRole } = render(
      <UiCheckbox onChange={mockOnChange} label={randomText} />
    );
    const checkboxInput: HTMLElement = getByRole('checkbox');
    fireEvent.click(checkboxInput);
    expect(mockOnChange).toHaveBeenCalled();
  });

  it('disables the checkbox when the disabled prop is true', () => {
    const mockOnChange: () => void = jest.fn();
    const { getByRole } = render(
      <UiCheckbox disabled onChange={mockOnChange} label={randomText} />
    );
    const checkboxInput: HTMLElement = getByRole('checkbox');
    expect(checkboxInput).toBeDisabled();
  });
});
