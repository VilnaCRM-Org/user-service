import { render } from '@testing-library/react';
import React from 'react';
import '@testing-library/jest-dom';

import RegistrationText from './RegistrationText';

const mainHeadingText: string = 'unlimited_possibilities.main_heading_text';
const secondaryHeadingText: string =
  'unlimited_possibilities.secondary_heading_text';

describe('RegistrationText component', () => {
  it('renders main and secondary heading text', () => {
    const { getByText } = render(<RegistrationText />);

    expect(getByText(mainHeadingText)).toBeInTheDocument();
    expect(getByText(secondaryHeadingText)).toBeInTheDocument();
  });
});
