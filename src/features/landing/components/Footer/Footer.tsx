import { Box } from '@mui/material';

import Adaptive from './Adaptive/Adaptive';
import { socialLinks } from './dataArray';
import { DefaultFooter } from './DefaultFooter';
import styles from './styles';

function Footer(): React.ReactElement {
  return (
    <Box component="footer" id="Contacts">
      <Box sx={styles.default}>
        <DefaultFooter socialLinks={socialLinks} />
      </Box>
      <Box sx={styles.adaptive}>
        <Adaptive socialLinks={socialLinks} />
      </Box>
    </Box>
  );
}

export default Footer;
