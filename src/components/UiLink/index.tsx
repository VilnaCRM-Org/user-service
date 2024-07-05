import { Link, LinkProps, ThemeProvider } from '@mui/material';

import { theme } from './theme';

function UiLink({ children, href, target, sx }: LinkProps): React.ReactElement {
  return (
    <ThemeProvider theme={theme}>
      <Link href={href} target={target} sx={sx}>
        {children}
      </Link>
    </ThemeProvider>
  );
}

export default UiLink;
