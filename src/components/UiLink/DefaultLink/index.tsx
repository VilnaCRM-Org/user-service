import { LinkProps, ThemeProvider } from '@mui/material';

import { theme } from './theme';

export default function defaultLink(Component: React.ComponentType<LinkProps>) {
  return function DefaultLink(props: LinkProps): React.ReactElement {
    const { children, href } = props as LinkProps;
    return (
      <ThemeProvider theme={theme}>
        <Component href={href}>{children}</Component>
      </ThemeProvider>
    );
  };
}
