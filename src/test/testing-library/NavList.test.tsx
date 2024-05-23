import { render } from '@testing-library/react';
import React from 'react';

import {
  testDrawerItem,
  testHeaderItem,
} from '../../features/landing/components/Header/constants';
import NavList from '../../features/landing/components/Header/NavList/NavList';
import { NavItemProps } from '../../features/landing/types/header/navigation';

const handleClick: () => void = jest.fn();

const navItems: NavItemProps[] = [testHeaderItem];
const drawerNavItems: NavItemProps[] = [testDrawerItem];

const navWrapperClass: string = '.MuiStack-root';
const navContentClass: string = '.MuiList-root';

const displayNoneStyle: string = 'display: none';
const flexColumnStyle: string = 'flex-direction: column';
const flexStyle: string = 'display: flex';
const marginTopStyle: string = 'margin-top: 6rem';

describe('NavList component', () => {
  it('renders NavList component correctly with header wrapper', () => {
    const { container } = render(
      <NavList navItems={navItems} handleClick={handleClick} />
    );

    expect(container.querySelector(navWrapperClass)).toBeInTheDocument();
    expect(container.querySelector(navWrapperClass)).toHaveStyle(
      displayNoneStyle
    );
    expect(container.querySelector(navWrapperClass)).toHaveStyle(
      flexColumnStyle
    );
    expect(container.querySelector(navWrapperClass)).not.toHaveStyle(
      marginTopStyle
    );
    expect(container.querySelector(navContentClass)).toBeInTheDocument();
    expect(container.querySelector(navContentClass)).toHaveStyle(flexStyle);
    expect(container.querySelector(navContentClass)).not.toHaveStyle(
      flexColumnStyle
    );
  });

  it('renders NavList component correctly with drawer wrapper', () => {
    const { container } = render(
      <NavList navItems={drawerNavItems} handleClick={handleClick} />
    );

    expect(container.querySelector(navWrapperClass)).toBeInTheDocument();
    expect(container.querySelector(navWrapperClass)).not.toHaveStyle(
      displayNoneStyle
    );
    expect(container.querySelector(navContentClass)).toBeInTheDocument();
    expect(container.querySelector(navContentClass)).toHaveStyle(
      flexColumnStyle
    );
  });

  it('renders NavList component correctly with empty array', () => {
    const { container } = render(
      <NavList navItems={[]} handleClick={handleClick} />
    );

    expect(container.querySelector(navWrapperClass)).not.toBeInTheDocument();
    expect(container.querySelector(navContentClass)).not.toBeInTheDocument();
  });
});
