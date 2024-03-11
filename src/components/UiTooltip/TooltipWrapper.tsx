import {
  ClickAwayListener,
  Tooltip,
  Typography,
  useMediaQuery,
} from '@mui/material';
import React from 'react';

import { UiTooltipProps } from './types';

export default function WrapperUiTooltip({
  children,
  title,
  placement,
  arrow,
  sx,
}: UiTooltipProps): React.ReactElement {
  const [open, setOpen] = React.useState(false);
  const isWideScreenMaxWidth: boolean = useMediaQuery('(max-width: 640px)');
  const isWideScreenMinWidth: boolean = useMediaQuery('(min-width: 640px)');

  React.useEffect(() => {
    if (isWideScreenMaxWidth || isWideScreenMinWidth) {
      setOpen(false);
    }
  }, [isWideScreenMaxWidth, isWideScreenMinWidth]);

  function handleTooltipClose(): void {
    setOpen(false);
  }
  function handleTooltipToogle(): void {
    setOpen(!open);
  }
  return (
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
  );
}
