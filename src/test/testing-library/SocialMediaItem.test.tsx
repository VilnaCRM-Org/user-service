import { render } from '@testing-library/react';
import React from 'react';

import {
  testSocialDrawerItem,
  testSocialNoDrawerItem,
} from '../../features/landing/components/SocialMedia/constants';
import SocialMediaItem from '../../features/landing/components/SocialMedia/SocialMediaItem/SocialMediaItem';

const widthStyle: string = 'width';
const heightStyle: string = 'height';
const imageRole: string = 'img';
const linkRole: string = 'link';

describe('SocialMediaItem', () => {
  it('renders social media drawer icon with correct attributes', () => {
    const { getByRole } = render(<SocialMediaItem item={testSocialDrawerItem} />);

    const linkElement: HTMLElement = getByRole(linkRole, {
      name: testSocialDrawerItem.ariaLabel,
    });
    const imageElement: HTMLElement = getByRole(imageRole);

    expect(linkElement).toBeInTheDocument();
    expect(linkElement).toHaveAttribute('href', testSocialDrawerItem.linkHref);
    expect(imageElement).toBeInTheDocument();
    expect(imageElement).toHaveAttribute(widthStyle, '24');
    expect(imageElement).toHaveAttribute(heightStyle, '24');
  });

  it('renders social media no drawer icon with correct attributes', () => {
    const { getByRole, debug } = render(<SocialMediaItem item={testSocialNoDrawerItem} />);

    debug();

    const linkElement: HTMLElement = getByRole(linkRole, {
      name: testSocialNoDrawerItem.ariaLabel,
    });
    const imageElement: HTMLElement = getByRole(imageRole);

    expect(linkElement).toBeInTheDocument();
    expect(linkElement).toHaveAttribute('href', testSocialNoDrawerItem.linkHref);
    expect(imageElement).toBeInTheDocument();
    expect(imageElement).toHaveAttribute(widthStyle, '20');
    expect(imageElement).toHaveAttribute(heightStyle, '20');
  });
});
