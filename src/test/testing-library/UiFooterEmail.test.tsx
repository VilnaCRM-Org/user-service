import { render } from '@testing-library/react';

import { VilnaCRMEmail } from '@/components/UiFooter/VilnaCRMEmail';

const mockEmail: string = 'info@vilnacrm.com';

describe('VilnaCRMEmail component', () => {
  it('renders email address correctly', () => {
    const { getByText } = render(<VilnaCRMEmail />);

    const emailLink: HTMLElement = getByText(mockEmail);
    expect(emailLink).toBeInTheDocument();
  });
});
