import { render } from '@testing-library/react';
import '@testing-library/jest-dom';

import TextInfo from './TextInfo';

const aboutUsText: string = 'about_vilna.text_main';
const aboutUsButtonText: string = 'about_vilna.button_main';

describe('code snippet', () => {
  it('should display correct text from translation file', () => {
    const { getByText, getByRole } = render(<TextInfo />);
    const buttonElement: HTMLElement = getByRole('button', {
      name: aboutUsButtonText,
    });

    expect(getByText(aboutUsText)).toBeInTheDocument();
    expect(buttonElement).toBeInTheDocument();
  });

  it('should display a link to sign up', () => {
    const { getByTestId } = render(<TextInfo />);
    expect(getByTestId('about-sign-up')).toBeInTheDocument();
  });
});
