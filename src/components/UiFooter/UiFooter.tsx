import { Box } from '@mui/material';

import { socialLinks } from './constants';
import { DefaultFooter } from './DefaultFooter';
import { Mobile } from './Mobile';
import styles from './styles';

function UiFooter(): React.ReactElement {
  return (
    <Box component="footer" id="Contacts">
      <Box sx={styles.default}>
        <DefaultFooter socialLinks={socialLinks} />
      </Box>
      <Box sx={styles.adaptive}>
        <Mobile socialLinks={socialLinks} />
      </Box>
    </Box>
  );
}

export default UiFooter;
