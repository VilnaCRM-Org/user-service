/* eslint-disable react/jsx-props-no-spreading */
import {
  ThemeProvider,
  Tooltip,
  TooltipProps,
  Typography,
} from '@mui/material';

import { theme } from './theme';

function UiTooltip(props: TooltipProps): React.ReactElement {
  const { children } = props;
  return (
    <ThemeProvider theme={theme}>
      <Tooltip {...props}>
        <Typography component="span">{children}</Typography>
      </Tooltip>
    </ThemeProvider>
  );
}
export default UiTooltip;
