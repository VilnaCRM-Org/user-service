import { render, screen } from '@testing-library/react';
import React from 'react';
import '@testing-library/jest-dom';

import ForWhoSection from './ForWhoSection';

const forWhoTestIdButton: string = 'for-who-sign-up';

describe('ForWhoSection component', () => {
  it('should render the ForWhoSection component without errors', () => {
    render(<ForWhoSection />);
    expect(screen.getByTestId(forWhoTestIdButton)).toBeInTheDocument();
  });
});
