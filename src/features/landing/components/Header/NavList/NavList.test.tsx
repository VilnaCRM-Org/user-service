import { render } from '@testing-library/react';
import React from 'react';

import { NavItemProps } from '../../../types/header/navigation';

import NavList from './NavList';

const navItems: NavItemProps[] = [
  { id: '1', title: 'Item 1', type: 'header', link: '/' },
  { id: '2', title: 'Item 2', type: 'header', link: '/' },
  { id: '3', title: 'Item 3', type: 'header', link: '/' },
];

const drawerNavItems: NavItemProps[] = [
  { id: '1', title: 'Item 1', type: 'drawer', link: '/' },
  { id: '2', title: 'Item 2', type: 'drawer', link: '/' },
  { id: '3', title: 'Item 3', type: 'drawer', link: '/' },
];

const navWrapperTestId: string = 'nav-wrapper';
const navContentTestId: string = 'nav-content';

describe('NavList component', () => {
  it('renders NavList component correctly with header wrapper', () => {
    const handleClick: () => void = jest.fn();
    const { getByTestId } = render(
      <NavList navItems={navItems} handleClick={handleClick} />
    );

    expect(getByTestId(navWrapperTestId)).toBeInTheDocument();
    expect(getByTestId(navContentTestId)).toBeInTheDocument();
  });

  it('renders NavList component correctly with drawer wrapper', () => {
    const handleClick: () => void = jest.fn();

    const { getByTestId } = render(
      <NavList navItems={drawerNavItems} handleClick={handleClick} />
    );

    expect(getByTestId(navWrapperTestId)).toBeInTheDocument();
    expect(getByTestId(navContentTestId)).toBeInTheDocument();
  });

  it('renders NavList component correctly with empty array', () => {
    const handleClick: () => void = jest.fn();
    const { queryByTestId } = render(
      <NavList navItems={[]} handleClick={handleClick} />
    );

    expect(queryByTestId(navWrapperTestId)).not.toBeInTheDocument();
    expect(queryByTestId(navContentTestId)).not.toBeInTheDocument();
  });
});
