import { render } from '@testing-library/react';

import PasswordTip from '../../features/landing/components/AuthSection/PasswordTip/PasswordTip';

const recommendationText: string = 'We recommend using:';
const firstOptionText: string = 'lowercase and uppercase special';
const secondOptionText: string = 'special characters (#&*$)';
const thirdOptionText: string = 'use numbers';

describe('PasswordTip component', () => {
  it('renders recommendation text', () => {
    const { getByText } = render(<PasswordTip />);
    expect(getByText(recommendationText)).toBeInTheDocument();
  });

  it('renders three password tip options', () => {
    const { getByText } = render(<PasswordTip />);
    expect(getByText(firstOptionText)).toBeInTheDocument();
    expect(getByText(secondOptionText)).toBeInTheDocument();
    expect(getByText(thirdOptionText)).toBeInTheDocument();
  });
});
