import '@testing-library/jest-dom';
import { render, fireEvent, waitFor } from '@testing-library/react';
import React from 'react';

import Drawer from '../../features/landing/components/Header/Drawer/Drawer';

const buttonToOpenDrawer: string = 'Button to open the drawer';
const buttonToCloseDrawer: string = 'Button to exit the drawer';
const logInButtonText: string = 'Log in';
const drawerImageAlt: string = 'Bars Icon';
const exitImageAlt: string = 'Exit Icon';
const logoAlt: string = 'Vilna logo';
const drawerContentRole: string = 'menu';
const listItem: string = 'listitem';

describe('Drawer', () => {
  it('renders drawer button', () => {
    const { getByLabelText, getByAltText } = render(<Drawer />);

    const drawerButton: HTMLElement = getByLabelText(buttonToOpenDrawer);
    const drawerImage: HTMLElement = getByAltText(drawerImageAlt);

    expect(drawerButton).toBeInTheDocument();
    expect(drawerImage).toBeInTheDocument();
  });

  it('opens drawer when button is clicked', async () => {
    const { getByLabelText, getByRole, getByAltText, getByText } = render(<Drawer />);

    const drawerButton: HTMLElement = getByLabelText(buttonToOpenDrawer);
    fireEvent.click(drawerButton);

    const drawer: HTMLElement = getByRole(drawerContentRole);
    const exitImage: HTMLElement = getByAltText(exitImageAlt);
    const logInButton: HTMLElement = getByText(logInButtonText);

    expect(drawer).toBeInTheDocument();
    expect(exitImage).toBeInTheDocument();
    expect(logInButton).toBeInTheDocument();
  });

  it('closes drawer when exit button is clicked', async () => {
    const { getByLabelText, queryByRole } = render(<Drawer />);

    const drawerButton: HTMLElement = getByLabelText(buttonToOpenDrawer);
    fireEvent.click(drawerButton);

    const exitButton: HTMLElement = getByLabelText(buttonToCloseDrawer);
    fireEvent.click(exitButton);

    const drawer: HTMLElement | null = queryByRole(drawerContentRole);
    expect(drawer).not.toBeInTheDocument();
  });

  it('renders logo', () => {
    const { getByLabelText, getByAltText } = render(<Drawer />);
    const drawerButton: HTMLElement = getByLabelText(buttonToOpenDrawer);

    fireEvent.click(drawerButton);
    const logo: HTMLElement = getByAltText(logoAlt);
    expect(logo).toBeInTheDocument();
  });

  it('renders nav items', () => {
    const { getByLabelText, getAllByRole } = render(<Drawer />);
    const drawerButton: HTMLElement = getByLabelText(buttonToOpenDrawer);
    fireEvent.click(drawerButton);
    const navItems: HTMLElement[] = getAllByRole(listItem);
    expect(navItems.length).toBeGreaterThan(0);
  });

  it('closes the drawer when handleCloseDrawer is called', async () => {
    const { getByRole, getByLabelText, queryByRole } = render(<Drawer />);

    const drawerButton: HTMLElement = getByLabelText(buttonToOpenDrawer);
    fireEvent.click(drawerButton);
    const tryItOutButton: HTMLElement = getByRole('button', {
      name: /Try it out/,
    });

    fireEvent.click(tryItOutButton);

    await waitFor(() => {
      expect(queryByRole(drawerContentRole)).not.toBeInTheDocument();
    });
  });
});
