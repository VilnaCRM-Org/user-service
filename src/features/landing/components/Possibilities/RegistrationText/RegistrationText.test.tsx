import { render } from '@testing-library/react';
import React from 'react';

import RegistrationText from './RegistrationText';

const mainHeadingText: string = 'Limitless';
const secondaryHeadingText: string = 'integration options';

describe('RegistrationText component', () => {
  it('renders main and secondary heading text', () => {
    const { getByText } = render(<RegistrationText />);

    expect(getByText(mainHeadingText)).toBeInTheDocument();
    expect(getByText(secondaryHeadingText)).toBeInTheDocument();
  });
});
