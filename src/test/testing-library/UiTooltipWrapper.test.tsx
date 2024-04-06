import { render, screen, fireEvent } from '@testing-library/react';
import React from 'react';

import WrapperUiTooltip from '@/components/UiTooltip/TooltipWrapper';
import { UiTooltipProps } from '@/components/UiTooltip/types';

jest.mock('@mui/material', () => ({
  ...jest.requireActual('@mui/material'),
  useMediaQuery: jest.fn(),
}));

describe('WrapperUiTooltip', () => {
  const setup: (props?: UiTooltipProps) => void = () =>
    render(<WrapperUiTooltip title="Tooltip Text">Trigger</WrapperUiTooltip>);

  afterEach(() => {
    jest.clearAllMocks();
  });

  it('renders the tooltip trigger', () => {
    setup();
    const trigger: HTMLElement = screen.getByText('Trigger');
    expect(trigger).toBeInTheDocument();
  });

  it('opens the tooltip on click', () => {
    setup();
    const trigger: HTMLElement = screen.getByText('Trigger');
    fireEvent.click(trigger);
    const tooltip: HTMLElement = screen.getByText('Tooltip Text');
    expect(tooltip).toBeInTheDocument();
  });

  it('closes the tooltip on click away', () => {
    setup();
    const trigger: HTMLElement = screen.getByText('Trigger');
    fireEvent.click(trigger);
    const tooltip: HTMLElement = screen.getByText('Tooltip Text');
    expect(tooltip).toBeInTheDocument();
  });

  it('closes the tooltip on width change', () => {
    setup();
    const trigger: HTMLElement = screen.getByText('Trigger');
    fireEvent.click(trigger);
    const tooltip: HTMLElement = screen.getByText('Tooltip Text');
    expect(tooltip).toBeInTheDocument();
  });
});
