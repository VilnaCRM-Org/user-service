import { Box } from '@mui/material';

import { ApiInfo } from './ApiInfo';
import { ApiLinks } from './ApiLinks';
import styles from './styles';

function AboutApi(): React.ReactElement {
  return (
    <Box sx={styles.aboutApiWrapper}>
      <ApiInfo />
      <ApiLinks />
    </Box>
  );
}

export default AboutApi;
