import { faker } from '@faker-js/faker';
import { ThemeProvider } from '@mui/material';
import { render, fireEvent } from '@testing-library/react';

import { UiButton } from '@/components';
import { theme } from '@/components/UiButton/theme';

const randomText: string = faker.lorem.word(8);

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
          {randomText}
        </UiButton>
      </ThemeProvider>
    );

    const button: HTMLElement = getByRole('button', { name: randomText });
    expect(button).toBeEnabled();
    expect(button).toBeInTheDocument();
  });

  it('calls the onClick handler when the button is clicked', () => {
    const onClick: () => void = jest.fn();
    const { getByRole } = render(
      <ThemeProvider theme={theme}>
        <UiButton onClick={onClick}>{randomText}</UiButton>
      </ThemeProvider>
    );

    const button: HTMLElement = getByRole('button', { name: randomText });
    fireEvent.click(button);
    expect(onClick).toHaveBeenCalledTimes(1);
  });
});
