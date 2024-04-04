import { render } from '@testing-library/react';
import React from 'react';
import '@testing-library/jest-dom';

import BackgroundImages from '../../features/landing/components/BackgroundImages/BackgroundImages';

const testStyle: string = `
background-color: red;
width: 100px;
height: 100px;
`;

jest.mock('./styles', () => ({
  vector: {
    backgroundColor: 'red',
    width: '100px',
    height: '100px',
  },
}));

describe('BackgroundImages Component', () => {
  it('renders with correct styles', () => {
    const { container } = render(<BackgroundImages />);

    expect(container.firstChild).toHaveStyle(testStyle);
  });
});
