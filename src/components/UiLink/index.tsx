import { Link, ThemeProvider } from '@mui/material';

import { theme } from './theme';
import { UiLinkProps } from './types';

function UiLink({ children, href, target }: UiLinkProps): React.ReactElement {
  return (
    <ThemeProvider theme={theme}>
      <Link href={href} target={target}>
        {children}
      </Link>
    </ThemeProvider>
  );
}

export default UiLink;
