import { render } from '@testing-library/react';
import React from 'react';

import {
  testDrawerItem,
  testHeaderItem,
} from '../../features/landing/components/Header/constants';
import NavItem from '../../features/landing/components/Header/NavItem/NavItem';

const handleClick: () => void = jest.fn();

const checkingStyle: string = 'width: 100%';

describe('NavItem component', () => {
  it('renders NavItem component correctly as header', () => {
    const { getByText, getByRole } = render(
      <NavItem item={testHeaderItem} handleClick={handleClick} />
    );

    const linkText: HTMLElement = getByText(testHeaderItem.title);
    const linkElement: HTMLElement = getByRole('link');

    expect(linkText).toBeInTheDocument();
    expect(linkElement).not.toHaveStyle(checkingStyle);
  });

  it('renders NavItem component correctly as drawer', () => {
    const { getByText, getByRole } = render(
      <NavItem item={testDrawerItem} handleClick={handleClick} />
    );

    const linkText: HTMLElement = getByText(testDrawerItem.title);
    const linkElement: HTMLElement = getByRole('link');

    expect(linkText).toBeInTheDocument();
    expect(linkElement).toHaveStyle(checkingStyle);
  });
});
