import { ThemeProvider } from '@mui/material';

import { UiButtonProps } from '../types';

import { theme } from './theme';

export default function mediumContainedBtn(
  Component: React.ComponentType<UiButtonProps>
) {
  return function MediumContainedBtn(props: UiButtonProps): React.ReactElement {
    const { onClick, sx, fullWidth, children, href, type } =
      props as UiButtonProps;
    return (
      <ThemeProvider theme={theme}>
        <Component
          onClick={onClick}
          sx={sx}
          fullWidth={fullWidth}
          href={href}
          type={type}
        >
          {children}
        </Component>
      </ThemeProvider>
    );
  };
}
