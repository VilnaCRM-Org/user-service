import { render } from '@testing-library/react';
import React from 'react';

import { NavItemProps } from '../../../types/header/navigation';
import '@testing-library/jest-dom';

import NavItem from './NavItem';

const drawerItem: NavItemProps = {
  id: '1',
  title: 'Item Title',
  link: '/item',
  type: 'drawer',
};

const item: NavItemProps = {
  id: '1',
  title: 'Item Title',
  link: '/item',
  type: 'header',
};

const ImageAlt: string = 'Vector';

describe('NavItem component', () => {
  it('renders NavItem component correctly as header', () => {
    const handleClick: () => void = jest.fn();
    const { getByText } = render(
      <NavItem item={item} handleClick={handleClick} />
    );

    const linkElement: HTMLElement = getByText(item.title);
    expect(linkElement).toBeInTheDocument();
  });

  it('renders NavItem component correctly as drawer', () => {
    const handleClick: () => void = jest.fn();
    const { getByText, getByAltText } = render(
      <NavItem item={drawerItem} handleClick={handleClick} />
    );

    const imageElement: HTMLElement = getByAltText(ImageAlt);
    const linkElement: HTMLElement = getByText(drawerItem.title);

    expect(linkElement).toBeInTheDocument();
    expect(imageElement).toBeInTheDocument();
  });
});
