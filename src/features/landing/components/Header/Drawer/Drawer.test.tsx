import { render, fireEvent, waitFor } from '@testing-library/react';
import React from 'react';

import Drawer from './Drawer';

const buttonToOpenDrawer: string = 'Button to open the drawer';
const buttonToCloseDrawer: string = 'Button to exit the drawer';
const logoAlt: string = 'Vilna logo';
const drawerTestId: string = 'drawer';
const listItem: string = 'listitem';

describe('Drawer', () => {
  it('renders drawer button', () => {
    const { getByLabelText } = render(<Drawer />);
    const drawerButton: HTMLElement = getByLabelText(buttonToOpenDrawer);
    expect(drawerButton).toBeInTheDocument();
  });

  it('opens drawer when button is clicked', () => {
    const { getByLabelText, getByTestId } = render(<Drawer />);
    const drawerButton: HTMLElement = getByLabelText(buttonToOpenDrawer);

    fireEvent.click(drawerButton);
    const drawer: HTMLElement = getByTestId(drawerTestId);
    expect(drawer).toBeInTheDocument();
  });

  it('closes drawer when exit button is clicked', async () => {
    const { getByLabelText, queryByTestId } = render(<Drawer />);
    const drawerButton: HTMLElement = getByLabelText(buttonToOpenDrawer);
    fireEvent.click(drawerButton);
    const exitButton: HTMLElement = getByLabelText(buttonToCloseDrawer);
    fireEvent.click(exitButton);

    await waitFor(() => {
      const drawer: HTMLElement | null = queryByTestId(drawerTestId);
      expect(drawer).not.toBeInTheDocument();
    });
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
});
