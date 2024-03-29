import { render } from '@testing-library/react';
import React from 'react';

import MainTitle from './MainTitle';

const forWhoTitle: string = 'For who';
const forWhoText: string =
  'We created Vilna, focusing on the specifics of the service business, which is not suitable for ordinary e-commerce templates';
const forWhoTestIdButton: string = 'for-who-sign-up';

describe('MainTitle component', () => {
  it('renders main title correctly', () => {
    const { getByText } = render(<MainTitle />);
    expect(getByText(forWhoTitle)).toBeInTheDocument();
  });

  it('renders main text correctly', () => {
    const { getByText } = render(<MainTitle />);
    expect(getByText(forWhoText)).toBeInTheDocument();
  });

  it('renders button correctly', () => {
    const { getByTestId } = render(<MainTitle />);
    expect(getByTestId(forWhoTestIdButton)).toBeInTheDocument();
    expect(getByTestId(forWhoTestIdButton)).toHaveAttribute('href', '#signUp');
  });
});
