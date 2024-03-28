import { render } from '@testing-library/react';
import React from 'react';
import '@testing-library/jest-dom';

import ServicesHoverCard from './ServicesHoverCard';

const hoverCardtitle: string = 'unlimited_possibilities.service_text.title';
const hoverCardtext: string = 'unlimited_possibilities.service_text.text';

describe('ServicesHoverCard component', () => {
  it('renders title and text correctly', () => {
    const { getByText } = render(<ServicesHoverCard />);

    expect(getByText(hoverCardtitle)).toBeInTheDocument();
    expect(getByText(hoverCardtext)).toBeInTheDocument();
  });

  it('renders images correctly', () => {
    const { getAllByAltText } = render(<ServicesHoverCard />);

    const images: HTMLElement[] = getAllByAltText(/.+/);
    expect(images.length).toBe(8);
  });
});
