import { render } from '@testing-library/react';
import React from 'react';

import {
  testDrawerItem,
  testHeaderItem,
} from '../../features/landing/components/Header/constants';
import NavItem from '../../features/landing/components/Header/NavItem/NavItem';

describe('NavItem component', () => {
  it('renders NavItem component correctly as header', () => {
    const handleClick: () => void = jest.fn();
    const { getByText } = render(
      <NavItem item={testHeaderItem} handleClick={handleClick} />
    );

    const linkElement: HTMLElement = getByText(testHeaderItem.title);
    expect(linkElement).toBeInTheDocument();
  });

  it('renders NavItem component correctly as drawer', () => {
    const handleClick: () => void = jest.fn();
    const { getByText } = render(
      <NavItem item={testDrawerItem} handleClick={handleClick} />
    );

    const linkElement: HTMLElement = getByText(testDrawerItem.title);

    expect(linkElement).toBeInTheDocument();
  });
});
