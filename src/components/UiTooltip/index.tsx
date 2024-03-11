import { ThemeProvider } from '@mui/material';
import React from 'react';

import { theme } from './theme';
import WrapperUiTooltip from './TooltipWrapper';
import { UiTooltipProps } from './types';

function UiTooltip(props: UiTooltipProps): React.ReactElement {
  return (
    <ThemeProvider theme={theme}>
      <WrapperUiTooltip {...props} />
    </ThemeProvider>
  );
}

export default UiTooltip;
