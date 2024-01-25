/* eslint-disable react/jsx-props-no-spreading */
import {
  Tooltip,
  TooltipProps,
  Typography,
  styled,
  tooltipClasses,
} from '@mui/material';

import { colorTheme } from '../UiColorTheme';

import styles from './styles';
import { UiTooltipProps } from './types';

const HtmlTooltip = styled(({ className, ...props }: TooltipProps) => (
  <Tooltip {...props} classes={{ popper: className }} />
))(() => ({
  [`& .${tooltipClasses.tooltip}`]: {
    backgroundColor: '#fff',
    color: 'rgba(0, 0, 0, 0.87)',
    maxWidth: '330px',
    border: `1px solid  ${colorTheme.palette.grey400.main}`,
    padding: '18px 24px',
    borderRadius: '8px',
    '@media (max-width: 1439.98px)': {
      display: 'none',
    },
  },
}));

function UiTooltip({ children, content, props }: UiTooltipProps) {
  return (
    <HtmlTooltip {...props} arrow title={content}>
      <Typography component="span" sx={styles.hoveredText}>
        {children}
      </Typography>
    </HtmlTooltip>
  );
}

export default UiTooltip;
