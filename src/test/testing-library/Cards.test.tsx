import { render } from '@testing-library/react';
import React from 'react';

import Cards from '../../features/landing/components/ForWhoSection/Cards/Cards';

const cardTitle: RegExp = /A private entrepreneur/;
const cardText: string = 'Our CRM is ideal if you:';
const cardBusinessText: RegExp = /medium-scale local project/;
const cardButton: string = 'Try it out';

describe('Cards component', () => {
  it('renders secondary title correctly', () => {
    const { getByText } = render(<Cards />);
    expect(getByText(cardTitle)).toBeInTheDocument();
  });

  it('renders secondary text correctly', () => {
    const { getByText } = render(<Cards />);
    expect(getByText(cardText)).toBeInTheDocument();
  });

  it('renders card items correctly', () => {
    const { getByText } = render(<Cards />);
    expect(getByText(cardBusinessText)).toBeInTheDocument();
  });

  it('renders button correctly', () => {
    const { getByText } = render(<Cards />);
    expect(getByText(cardButton)).toBeInTheDocument();
  });
});
