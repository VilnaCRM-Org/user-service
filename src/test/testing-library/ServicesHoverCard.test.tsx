import { render } from '@testing-library/react';
import React from 'react';

import ServicesHoverCard from '../../features/landing/components/Possibilities/ServicesHoverCard/ServicesHoverCard';

const hoverCardtitle: string = 'Services';
const hoverCardtext: string = 'Integrate in a few clicks';

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
