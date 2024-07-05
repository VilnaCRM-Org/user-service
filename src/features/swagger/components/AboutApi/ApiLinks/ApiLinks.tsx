import { Stack } from '@mui/material';

import { UiLink } from '@/components';

import { ApiDot } from '../ApiDot';

import styles from './styles';

function ApiLinks(): React.ReactElement {
  return (
    <Stack sx={styles.apiLinksWrapper}>
      <UiLink variant="medium15" sx={styles.url}>
        Terms of service
      </UiLink>
      <ApiDot color="blue" />
      <UiLink variant="medium15" sx={styles.url}>
        Contact the developer
      </UiLink>
      <ApiDot color="blue" />
      <UiLink variant="medium15" sx={styles.url}>
        Apache 2.0
      </UiLink>
      <ApiDot color="blue" />
      <UiLink variant="medium15" sx={styles.url}>
        Find out more about Swagger
      </UiLink>
    </Stack>
  );
}

export default ApiLinks;
