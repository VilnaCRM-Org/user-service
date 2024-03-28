import { fireEvent, render, screen } from '@testing-library/react';
import '@testing-library/jest-dom';
import { t } from 'i18next';

import Drawer from './Drawer';

const drawerTestId: string = 'drawer';
const closeButtonAriaLabel: string = 'header.drawer.button_aria_labels.exit';

describe('Drawer Component', () => {
  it('should not display the drawer when isDrawerOpen is false', () => {
    render(<Drawer />);

    const drawer: HTMLElement | null = screen.queryByTestId(drawerTestId);
    expect(drawer).not.toBeInTheDocument();
  });

  it('should close the drawer when the close button is clicked', () => {
    render(<Drawer />);
    const closeButton: HTMLElement = screen.getByRole('button', {
      name: t(closeButtonAriaLabel),
    });
    const drawerContent: HTMLElement | null = screen.queryByTestId('drawer');

    fireEvent.click(closeButton);
    expect(drawerContent).not.toBeInTheDocument();
  });
});
