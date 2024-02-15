import { ThemeProvider } from '@mui/material';

import { UiButtonProps } from '../types';

import { theme } from './theme';

export default function smallOutlinedBtn(
  Component: React.ComponentType<UiButtonProps>
) {
  return function SmallOutlinedBtn(props: UiButtonProps): React.ReactElement {
    const { onClick, sx, fullWidth, children, href } = props as UiButtonProps;
    return (
      <ThemeProvider theme={theme}>
        <Component onClick={onClick} sx={sx} fullWidth={fullWidth} href={href}>
          {children}
        </Component>
      </ThemeProvider>
    );
  };
}
