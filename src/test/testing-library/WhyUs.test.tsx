import { render } from '@testing-library/react';
import React from 'react';

import WhyUs from '../../features/landing/components/WhyUs';

jest.mock('../../components/UiCardList/CardSwiper', () => jest.fn());

const WrapperId: string = '#Advantages';
const signUpLinkLabelText: string = 'Link to registration';
const signUpButtonText: string = 'Try it out';

describe('SocialMediaItem', () => {
  it('render WhyUs component correctly', () => {
    const { container, getByLabelText, getByText } = render(<WhyUs />);

    const wrapperElement: HTMLElement | null = container.querySelector(WrapperId);
    const signUpLink: HTMLElement = getByLabelText(signUpLinkLabelText);
    const signUpButton: HTMLElement = getByText(signUpButtonText);

    expect(signUpLink).toBeInTheDocument();
    expect(wrapperElement).toBeInTheDocument();
    expect(signUpButton).toBeInTheDocument();
  });
});
