import { TooltipProps } from '@mui/material';

export interface UiTooltipProps {
  children: React.ReactNode;
  content: string | React.ReactNode;
  placement?: 'top' | 'bottom' | 'left' | 'right';
  props?: TooltipProps;
}
