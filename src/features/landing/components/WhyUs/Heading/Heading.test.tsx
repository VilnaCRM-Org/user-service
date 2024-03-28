import { render } from '@testing-library/react';
import '@testing-library/jest-dom';

import Heading from './Heading';

const headingText: string = 'why_us.heading';

it('renders heading and subtitle correctly', () => {
  const { getByText } = render(<Heading />);

  expect(getByText(headingText)).toBeInTheDocument();
});
