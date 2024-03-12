import {
  ClickAwayListener,
  Tooltip,
  Typography,
  useMediaQuery,
} from '@mui/material';
import React from 'react';

import { UiTooltipProps } from './types';

export default function WrapperUiTooltip({
  title,
  placement,
  arrow,
  sx,
  children,
}: UiTooltipProps): React.ReactElement {
  const [open, setOpen] = React.useState(false);
  const isWideScreenMaxWidth: boolean = useMediaQuery('(max-width: 640px)');
  const isWideScreenMinWidth: boolean = useMediaQuery('(min-width: 640px)');

  React.useEffect(() => {
    if (isWideScreenMaxWidth || isWideScreenMinWidth) {
      setOpen(false);
    }
  }, [isWideScreenMaxWidth, isWideScreenMinWidth]);

  const handleTooltipClose: () => void = () => {
    setOpen(false);
  };

  const handleTooltipToogle: () => void = () => {
    setOpen(!open);
  };

  return (
    <ClickAwayListener onClickAway={handleTooltipClose}>
      <Tooltip
        open={open}
        title={title}
        placement={placement}
        arrow={arrow}
        sx={sx}
      >
        <Typography component="span" onClick={handleTooltipToogle}>
          {children}
        </Typography>
      </Tooltip>
    </ClickAwayListener>
  );
}
