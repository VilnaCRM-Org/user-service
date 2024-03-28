import { render } from '@testing-library/react';
import React from 'react';
import '@testing-library/jest-dom';

import MainTitle from './MainTitle';

const forWhoTitle: string = 'for_who.heading_main';
const forWhoTestIdButton: string = 'for-who-sign-up';

describe('MainTitle component', () => {
  it('renders main title correctly', () => {
    const { getByText } = render(<MainTitle />);
    expect(getByText(forWhoTitle)).toBeInTheDocument();
  });

  it('renders button correctly', () => {
    const { getByTestId } = render(<MainTitle />);
    expect(getByTestId(forWhoTestIdButton)).toBeInTheDocument();
    expect(getByTestId(forWhoTestIdButton)).toHaveAttribute('href', '#signUp');
  });
});
