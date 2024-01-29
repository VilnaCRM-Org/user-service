import { TooltipProps } from '@mui/material';

export interface UiTooltipProps {
  children: React.ReactNode;
  content?: string | React.ReactNode;
  title?: string;
  placement?: 'top' | 'bottom' | 'left' | 'right';
  props?: TooltipProps;
  sx?: Record<string, unknown>;
}
