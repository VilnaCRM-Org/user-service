import { Box } from '@mui/material';

import Adaptive from './Adaptive/Adaptive';
import { socialLinks } from './dataArray';
import { DefaultFooter } from './DefaultFooter';
import styles from './styles';

function Footer() {
  // Can i use useContext for socialLinks?
  return (
    <>
      <Box sx={styles.default} component="footer" id="Contacts">
        <DefaultFooter socialLinks={socialLinks} />
      </Box>
      <Box sx={styles.adaptive} component="footer" id="Contacts">
        <Adaptive socialLinks={socialLinks} />
      </Box>
    </>
  );
}

export default Footer;
