import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import userEvent, { UserEvent } from '@testing-library/user-event';
import React from 'react';

import WrapperUiTooltip from '@/components/UiTooltip/TooltipWrapper';
import { UiTooltipProps } from '@/components/UiTooltip/types';

const triggerText: string = 'Trigger';
const tooltipContent: string = 'Tooltip Text';
const tooltipRole: string = 'tooltip';

jest.mock('@mui/material', () => ({
  ...jest.requireActual('@mui/material'),
  useMediaQuery: jest.fn(),
}));

describe('WrapperUiTooltip', () => {
  const setup: (props?: UiTooltipProps) => void = () =>
    render(<WrapperUiTooltip title={tooltipContent}>{triggerText}</WrapperUiTooltip>);

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

  it('open and clone tooltip', async () => {
    const user: UserEvent = userEvent.setup();
    setup();

    const trigger: HTMLElement = screen.getByText(triggerText);
    await user.click(trigger);

    let tooltip: HTMLElement | null = screen.getByRole(tooltipRole);
    expect(tooltip).toBeInTheDocument();

    await user.click(document.body);

    await waitFor(() => {
      tooltip = screen.queryByRole(tooltipRole);
      expect(tooltip).not.toBeInTheDocument();
    });
  });
});
