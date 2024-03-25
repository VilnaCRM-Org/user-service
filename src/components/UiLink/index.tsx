import { Link, ThemeProvider } from '@mui/material';

import { theme } from './theme';
import { UiLinkProps } from './types';

function UiLink({ children, href }: UiLinkProps): React.ReactElement {
  return (
    <ThemeProvider theme={theme}>
      <Link href={href}>{children}</Link>
    </ThemeProvider>
  );
}

export default UiLink;
