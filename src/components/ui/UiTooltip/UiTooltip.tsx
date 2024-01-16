/* eslint-disable react/jsx-props-no-spreading */
import {
  Tooltip,
  TooltipProps,
  Typography,
  styled,
  tooltipClasses,
} from '@mui/material';

import { uiTooltipStyles } from './styles';

const HtmlTooltip = styled(({ className, ...props }: TooltipProps) => (
  <Tooltip {...props} classes={{ popper: className }} />
))(() => ({
  [`& .${tooltipClasses.tooltip}`]: {
    backgroundColor: '#fff',
    color: 'rgba(0, 0, 0, 0.87)',
    maxWidth: '330px',
    border: '1px solid  #D0D4D8',
    padding: '18px 24px',
    borderRadius: '8px',
    '@media (max-width: 1439.98px)': {
      display: 'none',
    },
  },
}));

function UiTooltip({ children, content, props }: any) {
  return (
    <HtmlTooltip {...props} arrow title={content}>
      <Typography component="span" sx={uiTooltipStyles.hoveredText}>
        {children}
      </Typography>
    </HtmlTooltip>
  );
}

export default UiTooltip;
