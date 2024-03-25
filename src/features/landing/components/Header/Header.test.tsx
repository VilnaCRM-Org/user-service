import { render, screen } from '@testing-library/react';
import React from 'react';

import '@testing-library/jest-dom';
import Header from './Header';

describe('Header component', () => {
  it('renders logo', () => {
    render(<Header />);
    const logoElement: HTMLElement = screen.getByAltText('header.logo_alt');
    expect(logoElement).toBeInTheDocument();
  });
});
