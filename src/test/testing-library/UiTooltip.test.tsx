import { ThemeProvider } from '@mui/material';
import { render } from '@testing-library/react';
import React from 'react';

import { UiTooltip } from '@/components';
import { theme } from '@/components/UiTooltip/theme';

import { testText } from './constants';

const title: string = testText;
const placement: 'top' | 'bottom' | 'left' | 'right' = 'top';
const sx: object = { color: 'red' };
const children: React.ReactNode = <div>{testText}</div>;

describe('UiTooltip', () => {
  it('renders the tooltip with the correct props', () => {
    const { getByText } = render(
      <ThemeProvider theme={theme}>
        <UiTooltip title={title} placement={placement} arrow sx={sx}>
          {children}
        </UiTooltip>
      </ThemeProvider>
    );

    const trigger: HTMLElement = getByText(testText);

    expect(trigger).toBeInTheDocument();
  });
});
