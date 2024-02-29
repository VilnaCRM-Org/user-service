import {
  ClickAwayListener,
  ThemeProvider,
  Tooltip,
  Typography,
} from '@mui/material';
import React from 'react';

import { theme } from './theme';
import { UiTooltipProps } from './types';

function UiTooltip({
  children,
  title,
  placement,
  arrow,
  sx,
}: UiTooltipProps): React.ReactElement {
  const [open, setOpen] = React.useState(false);

  function handleTooltipClose(): void {
    setOpen(false);
  }
  function handleTooltipToogle(): void {
    setOpen(!open);
  }

  return (
    <ThemeProvider theme={theme}>
      <ClickAwayListener onClickAway={() => handleTooltipClose()}>
        <Tooltip
          open={open}
          title={title}
          placement={placement}
          arrow={arrow}
          sx={sx}
        >
          <Typography component="span" onClick={() => handleTooltipToogle()}>
            {children}
          </Typography>
        </Tooltip>
      </ClickAwayListener>
    </ThemeProvider>
  );
}
export default UiTooltip;
