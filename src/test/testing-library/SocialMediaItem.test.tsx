import { render } from '@testing-library/react';
import React from 'react';

import { testSocialItem } from '../../features/landing/components/SocialMedia/constants';
import SocialMediaItem from '../../features/landing/components/SocialMedia/SocialMediaItem/SocialMediaItem';

describe('SocialMediaItem', () => {
  it('renders social media icon with correct attributes', () => {
    const { getByRole } = render(<SocialMediaItem item={testSocialItem} />);

    const linkElement: HTMLElement = getByRole('link', {
      name: testSocialItem.ariaLabel,
    });
    expect(linkElement).toBeInTheDocument();
    expect(linkElement).toHaveAttribute('href', testSocialItem.linkHref);

    const imageElement: HTMLElement = getByRole('img');
    expect(imageElement).toBeInTheDocument();
  });
});
