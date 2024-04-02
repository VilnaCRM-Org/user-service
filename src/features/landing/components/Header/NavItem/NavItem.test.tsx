import { render } from '@testing-library/react';
import React from 'react';

import { testDrawerItem, testHeaderItem } from '../constants';

import NavItem from './NavItem';

const ImageAlt: string = 'Vector';

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
    const { getByText, getByAltText } = render(
      <NavItem item={testDrawerItem} handleClick={handleClick} />
    );

    const imageElement: HTMLElement = getByAltText(ImageAlt);
    const linkElement: HTMLElement = getByText(testDrawerItem.title);

    expect(linkElement).toBeInTheDocument();
    expect(imageElement).toBeInTheDocument();
  });
});
