import { render } from '@testing-library/react';
import React from 'react';

import { UiToolbar } from '@/components';

describe('UiToolbar', () => {
  it('renders the Toolbar with the children', () => {
    const testContent: string = 'This is a test content';
    const { getByText } = render(<UiToolbar>{testContent}</UiToolbar>);
    const toolbarElement: HTMLElement = getByText(testContent);
    expect(toolbarElement).toBeInTheDocument();
  });
});
