import { render } from '@testing-library/react';
import React from 'react';

import Logo from '../../../assets/svg/logo/Logo.svg';
import { SocialMedia } from '../../../types/social-media/index';

import SocialMediaList from './SocialMediaList';

jest.mock('../SocialMediaItem/SocialMediaItem', () =>
  jest.fn(() => <div data-testid="social-media-item" />)
);

const sociaLMediaTestId: string = 'social-media-item';
const emptySocialLinks: SocialMedia[] = [];
const socialLinks: SocialMedia[] = [
  {
    id: '1',
    alt: 'Twitter',
    linkHref: 'https://twitter.com/',
    ariaLabel: 'Twitter',
    icon: Logo,
  },
];

describe('SocialMediaList', () => {
  it('renders social media items correctly', () => {
    const { getAllByTestId } = render(
      <SocialMediaList socialLinks={socialLinks} />
    );

    const socialMediaItems: HTMLElement[] = getAllByTestId(sociaLMediaTestId);
    expect(socialMediaItems.length).toBe(socialLinks.length);
  });

  it('renders no social media items when socialLinks array is empty', () => {
    const { queryByTestId } = render(
      <SocialMediaList socialLinks={emptySocialLinks} />
    );

    const socialMediaItems: HTMLElement | null =
      queryByTestId(sociaLMediaTestId);
    expect(socialMediaItems).not.toBeInTheDocument();
  });
});
