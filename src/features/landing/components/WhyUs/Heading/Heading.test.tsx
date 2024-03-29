import { render } from '@testing-library/react';

import Heading from './Heading';

const subtitleText: RegExp = /Unlimited customization/;
const headingText: string = 'Why we';

describe('Heading component', () => {
  it('renders heading and subtitle correctly', () => {
    const { getByText } = render(<Heading />);
    expect(getByText(subtitleText)).toBeInTheDocument();
  });

  it('renders heading and text correctly', () => {
    const { getByText } = render(<Heading />);
    expect(getByText(headingText)).toBeInTheDocument();
  });
});
