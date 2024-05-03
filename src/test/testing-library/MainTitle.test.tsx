import { render } from '@testing-library/react';
import React from 'react';

import MainTitle from '../../features/landing/components/ForWhoSection/MainTitle/MainTitle';

const forWhoTitle: string = 'For who';
const forWhoText: RegExp = /We created Vilna,/;
const forWhoLabel: string = 'Link to registration';
const forWhoButton: string = 'Try it out';

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
    const { getByLabelText } = render(<MainTitle />);
    expect(getByLabelText(forWhoLabel)).toBeInTheDocument();
    expect(getByLabelText(forWhoLabel)).toHaveAttribute('href', '#signUp');
  });

  it('renders button correctly', () => {
    const { getByText } = render(<MainTitle />);
    expect(getByText(forWhoButton)).toBeInTheDocument();
  });
});
