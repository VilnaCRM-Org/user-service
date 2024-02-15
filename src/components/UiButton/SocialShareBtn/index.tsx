import { ThemeProvider } from '@mui/material';

import { UiButtonProps } from '../types';

import { theme } from './theme';

export default function socialShareBtn(
  Component: React.ComponentType<UiButtonProps>
) {
  return function SocialShareBtn(props: UiButtonProps): React.ReactElement {
    const { onClick, sx, children, href, name } = props as UiButtonProps;
    return (
      <ThemeProvider theme={theme}>
        <Component onClick={onClick} sx={sx} name={name} href={href}>
          {children}
        </Component>
      </ThemeProvider>
    );
  };
}
