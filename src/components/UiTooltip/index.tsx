import {
  ClickAwayListener,
  ThemeProvider,
  Tooltip,
  Typography,
  useMediaQuery,
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
  const isWideScreen: boolean = useMediaQuery(
    '(min-width: 639px) and (max-width: 641px)'
  );

  React.useEffect(() => {
    if (isWideScreen) {
      setOpen(false);
    }
  }, [isWideScreen]);
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
