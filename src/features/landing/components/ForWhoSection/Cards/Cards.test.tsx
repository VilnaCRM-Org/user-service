import { render } from '@testing-library/react';
import React from 'react';
import '@testing-library/jest-dom';

import Cards from './Cards';

const cardTitle: string = 'for_who.heading_secondary';
const cardBusinessText: string = 'for_who.card_text_business';
const cardButton: string = 'for_who.button_text';

describe('Cards component', () => {
  it('renders secondary title correctly', () => {
    const { getByText } = render(<Cards />);

    expect(getByText(cardTitle)).toBeInTheDocument();
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
