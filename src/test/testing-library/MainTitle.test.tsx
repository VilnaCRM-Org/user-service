import { render } from '@testing-library/react';
import React from 'react';

import MainTitle from '../../features/landing/components/ForWhoSection/MainTitle/MainTitle';

const forWhoTitle: string = 'For who';
const forWhoText: RegExp = /We created Vilna,/;
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
