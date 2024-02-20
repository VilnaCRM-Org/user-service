import { Link, LinkProps, ThemeProvider } from '@mui/material';

import { theme } from './theme';

function UiLink({ children, href }: LinkProps): React.ReactElement {
  return (
    <ThemeProvider theme={theme}>
      <Link href={href}>{children}</Link>
    </ThemeProvider>
  );
}

export default UiLink;
