import { render, screen } from '@testing-library/react';
import React from 'react';

import '@testing-library/jest-dom';
import { Header } from '../../components/Header';

describe('Header component', () => {
  test('renders logo', () => {
    render(<Header />);
    const logoElement: HTMLElement = screen.getByAltText('Vilna logo');
    expect(logoElement).toBeInTheDocument();
  });

  test('renders navigation items', () => {
    render(<Header />);
    const navListElement: HTMLElement = screen.getByRole('Advantages');
    expect(navListElement).toBeInTheDocument();
  });

  test('renders auth buttons', () => {
    render(<Header />);
    const authButtonsElement: HTMLElement = screen.getByText('Log in');
    expect(authButtonsElement).toBeInTheDocument();
  });
});
