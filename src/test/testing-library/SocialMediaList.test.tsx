import { render } from '@testing-library/react';
import React from 'react';

import { testSocialDrawerItem } from '../../features/landing/components/SocialMedia/constants';
import SocialMediaList from '../../features/landing/components/SocialMedia/SocialMediaList/SocialMediaList';
import { SocialMedia } from '../../features/landing/types/social-media/index';

jest.mock('../../features/landing/components/SocialMedia/SocialMediaItem/SocialMediaItem', () =>
  jest.fn(() => <div data-testid="social-media-item" />)
);

const sociaLMediaTestId: string = 'social-media-item';
const emptySocialLinks: SocialMedia[] = [];
const socialLinks: SocialMedia[] = [testSocialDrawerItem];

describe('SocialMediaList', () => {
  it('renders social media items correctly', () => {
    const { getAllByTestId } = render(<SocialMediaList socialLinks={socialLinks} />);

    const socialMediaItems: HTMLElement[] = getAllByTestId(sociaLMediaTestId);
    expect(socialMediaItems.length).toBe(socialLinks.length);
  });

  it('renders no social media items when socialLinks array is empty', () => {
    const { queryByTestId } = render(<SocialMediaList socialLinks={emptySocialLinks} />);

    const socialMediaItems: HTMLElement | null = queryByTestId(sociaLMediaTestId);
    expect(socialMediaItems).not.toBeInTheDocument();
  });
});
