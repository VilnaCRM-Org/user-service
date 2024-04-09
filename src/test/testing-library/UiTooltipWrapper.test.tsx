import { render, screen, fireEvent } from '@testing-library/react';
import React from 'react';

import WrapperUiTooltip from '@/components/UiTooltip/TooltipWrapper';
import { UiTooltipProps } from '@/components/UiTooltip/types';

const triggerText: string = 'Trigger';
const tooltipContent: string = 'Tooltip Text';

jest.mock('@mui/material', () => ({
  ...jest.requireActual('@mui/material'),
  useMediaQuery: jest.fn(),
}));

describe('WrapperUiTooltip', () => {
  const setup: (props?: UiTooltipProps) => void = () =>
    render(
      <WrapperUiTooltip title={tooltipContent}>{triggerText}</WrapperUiTooltip>
    );

  afterEach(() => {
    jest.clearAllMocks();
  });

  it('renders the tooltip trigger', () => {
    setup();
    const trigger: HTMLElement = screen.getByText(triggerText);
    expect(trigger).toBeInTheDocument();
  });

  it('opens the tooltip on click', () => {
    setup();
    const trigger: HTMLElement = screen.getByText(triggerText);
    fireEvent.click(trigger);
    const tooltip: HTMLElement = screen.getByText(tooltipContent);
    expect(tooltip).toBeInTheDocument();
  });

  it('closes the tooltip on click away', () => {
    setup();
    const trigger: HTMLElement = screen.getByText(triggerText);
    fireEvent.click(trigger);
    const tooltip: HTMLElement = screen.getByText(tooltipContent);
    expect(tooltip).toBeInTheDocument();
  });

  it('closes the tooltip on width change', () => {
    setup();
    const trigger: HTMLElement = screen.getByText(triggerText);
    fireEvent.click(trigger);
    const tooltip: HTMLElement = screen.getByText(tooltipContent);
    expect(tooltip).toBeInTheDocument();
  });
});
