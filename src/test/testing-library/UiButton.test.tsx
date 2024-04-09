import { ThemeProvider } from '@mui/material';
import { render, fireEvent } from '@testing-library/react';

import { UiButton } from '@/components';
import { theme } from '@/components/UiButton/theme';

import { testText } from './constants';

describe('UiButton', () => {
  it('renders the button with the correct props', () => {
    const onClick: () => void = jest.fn();
    const { getByRole } = render(
      <ThemeProvider theme={theme}>
        <UiButton
          variant="contained"
          size="medium"
          disabled={false}
          fullWidth={false}
          type="button"
          onClick={onClick}
          sx={{ color: 'red' }}
          name="my-button"
        >
          {testText}
        </UiButton>
      </ThemeProvider>
    );

    const button: HTMLElement = getByRole('button', { name: testText });
    expect(button).toBeEnabled();
    expect(button).toBeInTheDocument();
  });

  it('calls the onClick handler when the button is clicked', () => {
    const onClick: () => void = jest.fn();
    const { getByRole } = render(
      <ThemeProvider theme={theme}>
        <UiButton onClick={onClick}>{testText}</UiButton>
      </ThemeProvider>
    );

    const button: HTMLElement = getByRole('button', { name: testText });
    fireEvent.click(button);
    expect(onClick).toHaveBeenCalledTimes(1);
  });
});
