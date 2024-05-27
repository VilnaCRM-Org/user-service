import { render } from '@testing-library/react';

import SignUpText from '../../features/landing/components/AuthSection/SignUpText/SignUpText';

const socialTitle: string = 'Sign up now and free up your time to grow your business';
const vilnaText: string = 'VilnaCRM';
const socialsText: string = 'Log in with a convenient social network:';

describe('SignUpText Component', () => {
  it('should display title', () => {
    const { getByText } = render(<SignUpText socialLinks={[]} />);
    expect(getByText(socialTitle)).toBeInTheDocument();
    expect(getByText(vilnaText)).toBeInTheDocument();
  });

  it('should display text', () => {
    const { getByText } = render(<SignUpText socialLinks={[]} />);
    expect(getByText(socialsText)).toBeInTheDocument();
  });

  it('should render without crashing', () => {
    render(<SignUpText socialLinks={[]} />);
  });
});
