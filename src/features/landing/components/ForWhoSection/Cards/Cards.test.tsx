import { render } from '@testing-library/react';
import React from 'react';

import Cards from './Cards';

const cardTitle: string =
  'A private entrepreneur is a psychologist, tutor or dropshipper';
const cardText: string = 'Our CRM is ideal if you:';
const cardBusinessText: string =
  'medium-scale local project - online courses, design studio or small outsourcing';
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
