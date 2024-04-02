import '@testing-library/jest-dom';
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

  it('opens drawer when button is clicked', async () => {
    const { getByLabelText, getByTestId } = render(<Drawer />);
    const drawerButton: HTMLElement = getByLabelText(buttonToOpenDrawer);

    fireEvent.click(drawerButton);
    await waitFor(() => {
      const drawer: HTMLElement = getByTestId(drawerTestId);
      expect(drawer).toBeInTheDocument();
    });
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

  it('closes the drawer when handleCloseDrawer is called', async () => {
    const handleCloseDrawer: () => void = jest.fn();
    const { getByRole, getByLabelText, queryByTestId } = render(
      <Drawer handleCloseDrawer={handleCloseDrawer} />
    );

    const drawerButton: HTMLElement = getByLabelText(buttonToOpenDrawer);
    fireEvent.click(drawerButton);
    const tryItOutButton: HTMLElement = getByRole('button', {
      name: /Try it out/,
    });

    fireEvent.click(tryItOutButton);

    await waitFor(() => {
      expect(queryByTestId(drawerTestId)).not.toBeInTheDocument();
    });
  });

  it('should close drawer on wide screens', () => {
    const mockUseMediaQuery: jest.MockedFunction<() => boolean> = jest.fn(
      () => true
    );
    mockUseMediaQuery.mockReturnValue(true);
    jest.mock('@mui/material', () => ({
      ...jest.requireActual('@mui/material'),
      useMediaQuery: mockUseMediaQuery,
    }));

    const { queryByTestId } = render(<Drawer />);

    expect(queryByTestId(drawerTestId)).not.toBeInTheDocument();
  });
});
