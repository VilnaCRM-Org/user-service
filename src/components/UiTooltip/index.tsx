import { ThemeProvider, Tooltip, Typography } from '@mui/material';

import { theme } from './theme';
import { UiTooltipProps } from './types';

function UiTooltip({
  children,
  title,
  placement,
  arrow,
  sx,
}: UiTooltipProps): React.ReactElement {
  return (
    <ThemeProvider theme={theme}>
      <Tooltip title={title} placement={placement} arrow={arrow} sx={sx}>
        <Typography component="span">{children}</Typography>
      </Tooltip>
    </ThemeProvider>
  );
}
export default UiTooltip;
