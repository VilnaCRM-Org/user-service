import { render } from '@testing-library/react';

import TextInfo from '../../features/landing/components/AboutUs/TextInfo/TextInfo';

const aboutTitle: string = 'The first Ukrainian open source CRM';
const aboutUsText: RegExp = /Our goal/;
const aboutUsButtonText: string = 'Try it out';
const buttonTestId: string = 'about-sign-up';

describe('code snippet', () => {
  it('should display correct title from translation file', () => {
    const { getByText } = render(<TextInfo />);
    expect(getByText(aboutTitle)).toBeInTheDocument();
  });

  it('should display correct text from translation file', () => {
    const { getByText } = render(<TextInfo />);

    expect(getByText(aboutUsText)).toBeInTheDocument();
  });

  it('should display correct button from translation file', () => {
    const { getByRole } = render(<TextInfo />);

    const buttonElement: HTMLElement = getByRole('button', {
      name: aboutUsButtonText,
    });
    expect(buttonElement).toBeInTheDocument();
  });

  it('should display a link to sign up', () => {
    const { getByTestId } = render(<TextInfo />);
    expect(getByTestId(buttonTestId)).toBeInTheDocument();
  });
});
