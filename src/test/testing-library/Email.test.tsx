import { render } from '@testing-library/react';

import VilnaCRMEmail from '../../features/landing/components/Header/Drawer/VilnaCRMEmail/VilnaCRMEmail';

const mockEmail: string = 'info@vilnacrm.com';
const atSymbol: string = '@';

describe('VilnaCRMEmail component', () => {
  it('renders email address correctly', () => {
    const { getByText } = render(<VilnaCRMEmail />);

    const emailLink: HTMLElement = getByText(mockEmail);

    expect(emailLink).toBeInTheDocument();
  });

  it('renders "@" symbol correctly', () => {
    const { getByText } = render(<VilnaCRMEmail />);

    expect(getByText(atSymbol)).toBeInTheDocument();
  });
});
