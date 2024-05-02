import { render, screen, fireEvent } from '@testing-library/react';
import React from 'react';
import { useForm } from 'react-hook-form';

import { UiTextFieldForm } from '@/components';

import { testPlaceholder, testText } from './constants';

describe('UiTextFieldForm', () => {
  function TestWrapper(): React.ReactElement {
    const { control, handleSubmit } = useForm();
    const onSubmit: () => void = jest.fn();

    return (
      <form onSubmit={handleSubmit(onSubmit)}>
        <UiTextFieldForm
          control={control}
          name="testField"
          rules={{ required: true, minLength: 5 }}
          placeholder={testPlaceholder}
          type="text"
          fullWidth
        />
        <button type="submit">Submit</button>
      </form>
    );
  }

  it('renders the UiInput component with the correct props', () => {
    render(<TestWrapper />);

    const uiInput: HTMLElement = screen.getByRole('textbox');

    expect(uiInput).toHaveAttribute('type', 'text');
    expect(uiInput).toHaveAttribute('placeholder', testPlaceholder);
    expect(uiInput).toHaveValue('');
    expect(uiInput).not.toHaveAttribute('error');
  });

  it('updates the form field value on input change', () => {
    render(<TestWrapper />);

    const uiInput: HTMLElement = screen.getByRole('textbox');

    fireEvent.change(uiInput, { target: { value: testText } });

    expect(uiInput).toHaveValue(testText);
  });
});
