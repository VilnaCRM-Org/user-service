export interface UiTooltipProps {
  children: React.ReactNode;
  title: string | React.ReactNode;
  placement?: 'top' | 'bottom' | 'left' | 'right';
  arrow?: boolean;
  sx?: React.CSSProperties;
}
